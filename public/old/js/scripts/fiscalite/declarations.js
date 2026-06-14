import { get, post } from "../../modules/http.js";
import { fiscaliteMixin } from "./fiscalite-common.js";

new Vue({
    el: "#App",
    mixins: [fiscaliteMixin],
    data() {
        return {
            declarations: [],
            resultat: null,
            generating: false,
        };
    },
    methods: {
        async initPage() {
            await this.loadList();
        },
        async loadList() {
            const { data } = await get("/accounting/fiscalite/declarations/list");
            if (data.status === "success") {
                this.declarations = data.declarations || [];
            }
        },
        async generer() {
            this.generating = true;
            try {
                const { data } = await post(
                    `/accounting/fiscalite/declarations/generer?${this.queryParams()}`,
                    {}
                );
                if (!this.handleResponse(data)) return;
                this.resultat = data.data;
                await this.loadList();
            } finally {
                this.generating = false;
            }
        },
        async marquerDeposee(decl) {
            const { data } = await post("/accounting/fiscalite/declarations/deposer", {
                declaration_id: decl.id,
            });
            if (data.status === "success") {
                await this.loadList();
            } else if (data.errors) {
                this.error = data.errors;
            }
        },
    },
});
