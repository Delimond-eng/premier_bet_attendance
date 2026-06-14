import { get } from "../../modules/http.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin],
    data() {
        return {
            lignes: [],
            journalId: "",
        };
    },
    methods: {
        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const extra = {};
                if (this.journalId) {
                    extra.journal_id = this.journalId;
                }
                const { data } = await get(`/accounting/livres/journal/data?${this.queryParams(extra)}`);
                if (!this.handleResponse(data)) {
                    return;
                }
                this.lignes = data.lignes || [];
            } finally {
                this.isLoading = false;
            }
        },
    },
});
