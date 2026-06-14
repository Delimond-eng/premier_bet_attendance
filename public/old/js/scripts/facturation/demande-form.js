import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            demandeId: window.__DEMANDE_ID__ || null,
            demande: null,
            form: { montant: 0, devise: "CDF", motif: "" },
            traitement: { compte_debit: "", compte_credit: "", commentaire: "" },
        };
    },
    computed: {
        peutTraiter() {
            return this.demande && ["en_validation", "approuvee"].includes(this.demande.statut);
        },
    },
    async mounted() {
        await this.bootPage(async () => {
            if (this.demandeId) await this.loadDemande();
        });
    },
    methods: {
        async loadDemande() {
            const { data } = await get(`/accounting/facturation/demandes/${this.demandeId}/detail`);
            if (data.status === "success") {
                this.demande = data.demande;
                this.traitement.compte_debit = data.demande.compte_debit || "";
                this.traitement.compte_credit = data.demande.compte_credit || "";
            }
        },
        async creer() {
            const { data } = await postJson("/accounting/facturation/demandes/save", this.form);
            if (this.handleResponse(data) && data.demande?.id) {
                window.location.href = `/accounting/facturation/demandes/${data.demande.id}`;
            }
        },
        async traiter(decision) {
            const payload = { decision, ...this.traitement };
            if (this.demande?.etape_courante?.execution_paiement) {
                payload.executer_paiement = true;
                payload.methode = "caisse";
            }
            const { data } = await postJson(`/accounting/facturation/demandes/${this.demandeId}/traiter`, payload);
            if (this.handleResponse(data)) await this.loadDemande();
        },
    },
});
