import { get } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return { items: [], cible: window.__ECHEANCIER_CIBLE__ || "clients" };
    },
    async mounted() {
        await this.bootPage(async () => {
            const { data } = await get(`/accounting/facturation/echeancier?cible=${this.cible}`);
            if (data.status === "success") this.items = data.items || [];
        });
    },
});
