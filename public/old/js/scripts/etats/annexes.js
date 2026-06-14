import { get } from "../../modules/http.js";
import { etatsMixin } from "./etats-common.js";

new Vue({
    el: "#App",
    mixins: [etatsMixin],
    methods: {
        async initPage() {
            await this.loadData();
        },
        async loadData() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/etats/annexes/data");
                if (!this.handleResponse(data)) return;
                this.data = data.data;
            } finally {
                this.isLoading = false;
            }
        },
    },
});
