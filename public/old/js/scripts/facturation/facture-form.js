import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            tiers: [],
            form: {
                id: window.__FACTURE_ID__ || null,
                type_document: window.__TYPE_DOCUMENT__ || "vente_client",
                tiers_id: null,
                date_facture: new Date().toISOString().slice(0, 10),
                date_echeance: "",
                objet: "",
                tva_active: false,
                taux_tva: 16,
                devise: "CDF",
                lignes: [{ libelle: "", quantite: 1, prix_unitaire: 0 }],
            },
            totaux: { ht: 0, tva: 0, ttc: 0 },
        };
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            if (this.meta?.taux_tva_defaut) this.form.taux_tva = this.meta.taux_tva_defaut;
            await this.loadTiers();
            if (this.form.id) await this.loadFacture();
            this.recalc();
        });
    },
    methods: {
        async loadTiers() {
            const cible = this.form.type_document.includes("achat") || this.form.type_document.includes("fournisseur") ? "fournisseur" : "client";
            const { data } = await get(`/accounting/facturation/tiers?cible=${cible}`);
            if (data.status === "success") this.tiers = data.tiers || [];
        },
        async loadFacture() {
            const { data } = await get(`/accounting/facturation/factures/${this.form.id}`);
            if (data.status !== "success") return;
            const f = data.facture;
            this.form = {
                id: f.id,
                type_document: f.type_document,
                tiers_id: f.tiers_id,
                date_facture: String(f.date_facture).slice(0, 10),
                date_echeance: f.date_echeance ? String(f.date_echeance).slice(0, 10) : "",
                objet: f.objet || "",
                tva_active: !!f.tva_active,
                taux_tva: Number(f.taux_tva) || 16,
                devise: f.devise || "CDF",
                lignes: (f.lignes || []).map((l) => ({
                    libelle: l.libelle,
                    quantite: Number(l.quantite),
                    prix_unitaire: Number(l.prix_unitaire),
                    compte_comptable: l.compte_comptable,
                    produit_id: l.produit_id,
                })),
            };
        },
        ligneHt(l) {
            return (Number(l.quantite) || 0) * (Number(l.prix_unitaire) || 0);
        },
        recalc() {
            let ht = 0;
            this.form.lignes.forEach((l) => {
                ht += this.ligneHt(l);
            });
            ht = Math.round(ht * 100) / 100;
            const tva = this.form.tva_active ? Math.round(ht * this.form.taux_tva) / 100 : 0;
            this.totaux = { ht, tva, ttc: Math.round((ht + tva) * 100) / 100 };
        },
        addLigne() {
            this.form.lignes.push({ libelle: "", quantite: 1, prix_unitaire: 0 });
        },
        async save(etValider) {
            this.isLoading = true;
            try {
                const payload = { ...this.form, lignes: this.form.lignes.filter((l) => l.libelle) };
                const { data } = await postJson("/accounting/facturation/factures/save", payload);
                if (!this.handleResponse(data)) return;
                if (etValider && data.facture?.id) {
                    const v = await postJson(`/accounting/facturation/factures/${data.facture.id}/valider`, {});
                    this.handleResponse(v);
                }
                if (data.facture?.id) {
                    const base = this.form.type_document.includes("achat") ? "fournisseurs" : "clients";
                    window.location.href = `/accounting/facturation/${base}`;
                }
            } finally {
                this.isLoading = false;
            }
        },
    },
});
