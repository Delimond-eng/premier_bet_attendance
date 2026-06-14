import { get, postJson } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return {
            produits: [],
            form: { libelle: "", prix_unitaire: 0, compte_vente: "701100", compte_achat: "601100" },
        };
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadProduits();
        });
    },
    methods: {
        async loadProduits() {
            const { data } = await get("/accounting/facturation/produits/list");
            if (data.status === "success") this.produits = data.produits || [];
        },
        async save() {
            const { data } = await postJson("/accounting/facturation/produits/save", this.form);
            if (this.handleResponse(data)) {
                this.form = { libelle: "", prix_unitaire: 0, compte_vente: "701100", compte_achat: "601100" };
                await this.loadProduits();
            }
        },
    },
});
