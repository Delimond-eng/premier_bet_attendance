import { get, postJson } from "../../modules/http.js";
import { compteSelectMixin } from "../../modules/compte-select-mixin.js";
import { saisieMixin } from "./saisie-common.js";

new Vue({
    el: "#App",
    mixins: [saisieMixin, compteSelectMixin],
    data() {
        return {
            ecritureId: window.__ECRITURE_ID__ || null,
            multiDevise: false,
            journalVerrouille: false,
            template: [],
            tiersOptions: [],
            entete: {
                id: null,
                exercice_id: null,
                journal_id: null,
                date_ecriture: new Date().toISOString().slice(0, 10),
                date_echeance: null,
                libelle: "",
                reference_facture: "",
                reference_externe: "",
                devise: "CDF",
                taux_change: 1,
                type_ecriture: "normale",
            },
            lignes: [],
        };
    },

    computed: {
        listeUrl() {
            return `/accounting/saisie/${this.page}`;
        },
        totalDebit() {
            return this.lignes.reduce((s, l) => s + (parseFloat(l.debit) || 0), 0);
        },
        totalCredit() {
            return this.lignes.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0);
        },
        ecart() {
            return Math.abs(this.totalDebit - this.totalCredit);
        },
        equilibre() {
            return this.totalDebit > 0 && this.ecart < 0.01;
        },
    },

    methods: {
        async initPage() {
            await this.loadTiers();
            this.initEntete();
            if (this.ecritureId) {
                await this.loadEcriture(this.ecritureId);
            } else {
                this.appliquerTemplate();
            }
            await this.prefetchComptesFromLignes(this.lignes);
        },

        initEntete() {
            if (this.exercice) this.entete.exercice_id = this.exercice.id;
            if (this.journal) {
                this.entete.journal_id = this.journal.id;
                this.journalVerrouille = !!this.journal.id && this.page !== "nouvelle";
            }
            this.entete.devise = this.devisePrincipale || "CDF";
        },

        async loadEcriture(id) {
            const { data } = await get(`/accounting/saisie/ecritures/${id}`);
            if (data.status !== "success") return;
            const e = data.ecriture;
            this.entete = {
                id: e.id,
                exercice_id: e.exercice_id,
                journal_id: e.journal_id,
                date_ecriture: e.date_ecriture?.substring?.(0, 10) || e.date_ecriture,
                date_echeance: e.date_echeance?.substring?.(0, 10) || null,
                libelle: e.libelle,
                reference_facture: e.reference_facture,
                reference_externe: e.reference_externe,
                devise: e.devise,
                taux_change: e.taux_change,
                type_ecriture: e.type_ecriture,
            };
            this.lignes = (e.lignes || []).map((l) => ({
                num_compte: l.num_compte,
                libelle: l.libelle,
                debit: l.debit,
                credit: l.credit,
                tiers_id: l.tiers_id,
                montant_devise: l.montant_devise,
                taux_change: l.taux_change,
            }));
        },

        async loadTiers() {
            const { data } = await get("/accounting/saisie/tiers/search?q=");
            if (data.status === "success") this.tiersOptions = data.tiers || [];
        },

        appliquerTemplate() {
            if (this.template?.length) {
                this.lignes = this.template.map((l) => ({ ...l, debit: 0, credit: 0 }));
            } else {
                this.lignes = [
                    { num_compte: "", libelle: "", debit: 0, credit: 0, tiers_id: null },
                    { num_compte: "", libelle: "", debit: 0, credit: 0, tiers_id: null },
                ];
            }
            if (this.entete.libelle === "" && this.journal) {
                this.entete.libelle = "Écriture " + this.journal.libelle;
            }
        },

        ajouterLigne() {
            this.lignes.push({ num_compte: "", libelle: this.entete.libelle, debit: 0, credit: 0, tiers_id: null });
        },

        supprimerLigne(idx) {
            if (this.lignes.length > 2) this.lignes.splice(idx, 1);
        },

        onMontant(ligne, col) {
            if (col === "debit" && ligne.debit > 0) ligne.credit = 0;
            if (col === "credit" && ligne.credit > 0) ligne.debit = 0;
        },

        async fetchTaux() {
            const { data } = await get(
                `/accounting/saisie/taux?devise=${this.entete.devise}&date=${this.entete.date_ecriture}`
            );
            if (data.status === "success") this.entete.taux_change = data.taux;
        },

        async save(valider) {
            if (!this.equilibre) {
                this.error = ["L'écriture doit être équilibrée (débit = crédit)."];
                return;
            }
            this.isLoading = true;
            const { data } = await postJson("/accounting/saisie/ecritures/store", {
                entete: this.entete,
                lignes: this.lignes,
                valider,
            });
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            if (data.ecriture?.id) {
                if (!this.warnings?.length) {
                    window.location.href = this.listeUrl;
                }
            }
        },
    },
});
