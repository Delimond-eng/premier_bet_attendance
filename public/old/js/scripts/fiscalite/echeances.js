import { get } from "../../modules/http.js";
import { fiscaliteMixin } from "./fiscalite-common.js";

new Vue({
    el: "#App",
    mixins: [fiscaliteMixin],
    data() {
        return {
            echeances: [],
        };
    },
    methods: {
        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/fiscalite/echeances/data");
                if (!this.handleResponse(data)) return;
                this.echeances = data.echeances || [];
            } finally {
                this.isLoading = false;
            }
        },
        isRetard(e) {
            if (e.statut === "deposee") return false;
            return e.date_limite_depot && e.date_limite_depot < new Date().toISOString().slice(0, 10);
        },
    },
});
