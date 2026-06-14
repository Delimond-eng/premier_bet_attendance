import { get } from "../../modules/http.js";
import { etatsMixin } from "./etats-common.js";

new Vue({
    el: "#App",
    mixins: [etatsMixin],
    methods: {
        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const { data, status } = await get(`/accounting/etats/bilan/data?${this.queryParams()}`);
                const hasBilan = data?.data?.actif != null && data?.data?.passif != null;

                if (status >= 400) {
                    this.handleResponse(data);
                    if (hasBilan) {
                        this.data = data.data;
                    } else {
                        this.data = null;
                        if (!this.error?.length) {
                            this.error = ["Impossible de charger le bilan (erreur serveur)."];
                        }
                    }
                    return;
                }

                if (!this.handleResponse(data)) {
                    this.data = null;
                    return;
                }
                if (data.status !== "success" || !data.data) {
                    this.error = ["Réponse invalide du serveur."];
                    this.data = null;
                    return;
                }
                this.data = data.data;
                if (data.data?.exercice_n1) {
                    this.exerciceN1 = { libelle: data.data.exercice_n1 };
                }
            } catch (e) {
                console.error(e);
                this.error = [e.message || "Erreur réseau lors du chargement du bilan."];
                this.data = null;
            } finally {
                this.isLoading = false;
            }
        },
    },
});
