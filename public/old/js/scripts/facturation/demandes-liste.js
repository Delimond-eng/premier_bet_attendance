import { get } from "../../modules/http.js";
import { facturationMixin } from "./facturation-common.js";

new Vue({
    el: "#App",
    mixins: [facturationMixin],
    data() {
        return { demandes: [] };
    },
    async mounted() {
        await this.bootPage(async () => {
            await this.loadList();
        });
    },
    methods: {
        async loadList() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/facturation/demandes/list");
                if (data.status === "success") this.demandes = data.demandes || [];
            } finally {
                this.isLoading = false;
            }
        },
    },
});
