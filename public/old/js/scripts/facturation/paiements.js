import { get } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return { paiements: [] };
    },
    async mounted() {
        await this.bootPage(async () => {
            const { data } = await get("/accounting/facturation/paiements/list");
            if (data.status === "success") this.paiements = data.paiements || [];
        });
    },
});
