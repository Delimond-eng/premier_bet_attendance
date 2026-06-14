import { get } from "../../modules/http.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin],
    data() {
        return {
            tiers: [],
        };
    },
    methods: {
        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const { data } = await get(`/accounting/livres/comptes-tiers/data?${this.queryParams()}`);
                if (!this.handleResponse(data)) {
                    return;
                }
                this.tiers = data.tiers || [];
            } finally {
                this.isLoading = false;
            }
        },
        labelType(t) {
            return { client: "Client", fournisseur: "Fournisseur", personnel: "Personnel" }[t] || t;
        },
    },
});
