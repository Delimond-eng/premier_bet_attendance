import { get } from "../../modules/http.js";
import { compteSelectMixin } from "../../modules/compte-select-mixin.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin, compteSelectMixin],
    data() {
        return {
            lignes: [],
            numCompte: "41",
            tiersId: "",
        };
    },
    methods: {
        queryParams() {
            const p = new URLSearchParams();
            const compte = (this.numCompte || "").trim();
            if (compte) {
                p.set("num_compte", compte);
            }
            if (this.tiersId) {
                p.set("tiers_id", this.tiersId);
            }
            return p.toString();
        },

        async initPage() {
            if (this.numCompte) {
                await this.prefetchCompteLabel(this.numCompte);
            }
            await this.loadData();
        },

        async loadData() {
            this.isLoading = true;
            try {
                const p = new URLSearchParams();
                const compte = (this.numCompte || "").trim();
                if (compte) {
                    p.set("num_compte", compte);
                }
                if (this.tiersId) {
                    p.set("tiers_id", this.tiersId);
                }
                const { data } = await get(`/accounting/livres/lettrage/data?${p}`);
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
