import { get } from "../../modules/http.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin],
    data() {
        return {
            lignes: [],
            totaux: null,
            classe: "",
        };
    },
    methods: {
        queryParams(extra = {}) {
            const e = { ...extra };
            if (this.classe) {
                e.classe = this.classe;
            }
            return livresMixin.methods.queryParams.call(this, e);
        },

        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const extra = {};
                if (this.classe) {
                    extra.classe = this.classe;
                }
                const { data } = await get(`/accounting/livres/balance/data?${this.queryParams(extra)}`);
                if (!this.handleResponse(data)) {
                    return;
                }
                this.lignes = data.data?.lignes || [];
                this.totaux = data.data?.totaux || null;
            } finally {
                this.isLoading = false;
            }
        },
    },
});
