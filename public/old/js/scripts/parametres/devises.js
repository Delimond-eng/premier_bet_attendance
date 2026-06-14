import { get, postJson } from "../../modules/http.js";
import { parametresMixin } from "./parametres-common.js";

new Vue({
    el: "#App",
    mixins: [parametresMixin],
    data() {
        return {
            devises: [],
            taux: [],
            formTaux: {
                devise_code: "USD",
                date_taux: new Date().toISOString().slice(0, 10),
                taux: 1,
                taux_achat: null,
                taux_vente: null,
            },
        };
    },

    methods: {
        async initPage() {
            await this.loadDevises();
        },

        async onSocieteChanged() {
            await this.loadDevises();
        },

        async loadDevises() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/parametres/devises/all");
                if (data.status === "success") {
                    this.devises = data.devises || [];
                    this.taux = data.taux || [];
                }
            } finally {
                this.isLoading = false;
            }
        },

        openTauxForm() {
            new bootstrap.Modal(document.getElementById("modal_taux")).show();
        },

        async saveTaux() {
            this.isLoading = true;
            const { data } = await postJson("/accounting/parametres/taux-change/save", this.formTaux);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            bootstrap.Modal.getInstance(document.getElementById("modal_taux"))?.hide();
            this.loadDevises();
        },
    },
});
