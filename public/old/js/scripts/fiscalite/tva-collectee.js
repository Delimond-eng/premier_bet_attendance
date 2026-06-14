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
                const { data } = await get(`/accounting/fiscalite/tva-collectee/data?${this.queryParams()}`);
                if (!this.handleResponse(data)) return;
                this.data = data.data;
            } finally {
                this.isLoading = false;
            }
        },
    },
});
