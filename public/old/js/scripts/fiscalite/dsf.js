import { get } from "../../modules/http.js";
import { fiscaliteMixin } from "./fiscalite-common.js";

new Vue({
    el: "#App",
    mixins: [fiscaliteMixin],
    methods: {
        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams({
                    date_fin: this.filtres.date_fin,
                    exercice_id: this.filtres.exercice_id || "",
                    devise_affichage: this.filtres.devise_affichage,
                    mode_conversion: this.filtres.mode_conversion,
                }).toString();
                const { data } = await get(`/accounting/fiscalite/dsf/data?${params}`);
                if (!this.handleResponse(data)) return;
                this.data = data.data;
            } finally {
                this.isLoading = false;
            }
        },
    },
});
