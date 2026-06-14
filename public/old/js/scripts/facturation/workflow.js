import { get } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return { workflows: [] };
    },
    async mounted() {
        await this.bootPage(async () => {
            const { data } = await get("/accounting/facturation/workflow/list");
            if (data.status === "success") this.workflows = data.workflows || [];
        });
    },
});
