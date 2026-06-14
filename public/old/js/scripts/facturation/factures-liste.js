import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

let timer = null;

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            page: window.__FACTURATION_PAGE__ || "clients",
            typeDocument: window.__FACTURATION_TYPE__ || "vente_client",
            factures: [],
            search: "",
            filtreStatut: "",
        };
    },
    computed: {
        createUrl() {
            const base = this.page.includes("fournisseur") ? "fournisseurs" : "clients";
            if (this.typeDocument.includes("avoir")) {
                return `/accounting/facturation/${this.page.includes("fournisseur") ? "avoirs-fournisseurs" : "avoirs-clients"}`;
            }
            return `/accounting/facturation/${base}/nouvelle`;
        },
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            await this.loadList();
        });
    },
    methods: {
        debounceLoad() {
            clearTimeout(timer);
            timer = setTimeout(() => this.loadList(), 350);
        },
        async loadList() {
            this.isLoading = true;
            const p = new URLSearchParams({ type_document: this.typeDocument });
            if (this.search) p.set("search", this.search);
            if (this.filtreStatut) p.set("statut", this.filtreStatut);
            try {
                const { data } = await get(`/accounting/facturation/factures?${p}`);
                if (data.status === "success") this.factures = data.factures || [];
            } finally {
                this.isLoading = false;
            }
        },
        editUrl(id) {
            const base = this.page.includes("fournisseur") || this.typeDocument.includes("achat") ? "fournisseurs" : "clients";
            return `/accounting/facturation/${base}/${id}`;
        },
        async valider(f) {
            if (!confirm(`Valider la facture ${f.numero} ? Une écriture comptable sera générée.`)) return;
            const { data } = await postJson(`/accounting/facturation/factures/${f.id}/valider`, {});
            if (this.handleResponse(data)) this.loadList();
        },
        async payer(f) {
            const methode = prompt("Méthode : banque ou caisse ?", "banque");
            if (!methode) return;
            const { data } = await postJson(`/accounting/facturation/paiements/facture/${f.id}`, {
                methode: methode === "caisse" ? "caisse" : "banque",
                montant: f.montant_ttc,
            });
            if (this.handleResponse(data)) {
                alert("Paiement enregistré. Téléchargez le reçu depuis Paiements.");
                this.loadList();
            }
        },
    },
});
