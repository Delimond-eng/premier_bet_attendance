import { get, postJson } from "../../modules/http.js";
import { saisieMixin } from "./saisie-common.js";

let timer = null;

new Vue({
    el: "#App",
    mixins: [saisieMixin],
    data() {
        return {
            ecritures: [],
            search: "",
            filtreStatut: "",
        };
    },

    computed: {
        createUrl() {
            return `/accounting/saisie/${this.page}/ecriture`;
        },
    },

    methods: {
        async initPage() {
            await this.loadList();
        },

        debounceLoad() {
            clearTimeout(timer);
            timer = setTimeout(() => this.loadList(), 350);
        },

        async loadList() {
            this.isLoading = true;
            try {
                const { data } = await get(`/accounting/saisie/ecritures?${this.queryParams()}`);
                if (data.status === "success") this.ecritures = data.ecritures || [];
            } finally {
                this.isLoading = false;
            }
        },

        async valider(e) {
            if (!confirm(`Valider l'écriture ${e.num_piece} ?`)) return;
            const { data } = await postJson(`/accounting/saisie/ecritures/${e.id}/validate`, {});
            if (this.handleResponse(data)) this.loadList();
        },

        async supprimer(e) {
            if (!confirm(`Supprimer le brouillon ${e.num_piece} ?`)) return;
            const { data } = await postJson(`/accounting/saisie/ecritures/${e.id}/delete`, {});
            if (this.handleResponse(data)) this.loadList();
        },
    },
});
