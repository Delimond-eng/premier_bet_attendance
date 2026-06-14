import { get, postJson } from "../../modules/http.js";
import { compteSelectMixin } from "../../modules/compte-select-mixin.js";
import { parametresMixin } from "./parametres-common.js";

let searchTimer = null;

new Vue({
    el: "#App",
    mixins: [parametresMixin, compteSelectMixin],
    data() {
        return {
            liste: [],
            search: "",
            filtreType: "",
            form: this.emptyForm(),
            exportBase: "/accounting/export/parametres/tiers",
        };
    },

    methods: {
        queryParams() {
            const p = new URLSearchParams();
            if (this.search) {
                p.set("search", this.search);
            }
            if (this.filtreType) {
                p.set("type", this.filtreType);
            }
            return p.toString();
        },

        async initPage() {
            await this.loadTiers();
        },

        async onSocieteChanged() {
            await this.loadTiers();
        },

        emptyForm() {
            return {
                id: null,
                code: "",
                nom: "",
                type: "client",
                num_compte_collectif: "",
                email: "",
                telephone: "",
                ville: "",
                actif: true,
            };
        },

        debounceSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => this.loadTiers(), 350);
        },

        async loadTiers() {
            this.isLoading = true;
            const params = new URLSearchParams();
            if (this.search) params.set("search", this.search);
            if (this.filtreType) params.set("type", this.filtreType);
            try {
                const { data } = await get(`/accounting/parametres/tiers/all?${params}`);
                if (data.status === "success") this.liste = data.tiers || [];
            } finally {
                this.isLoading = false;
            }
        },

        openForm() {
            this.form = this.emptyForm();
            new bootstrap.Modal(document.getElementById("modal_tiers")).show();
        },

        async editTiers(t) {
            this.form = { ...t };
            if (t.num_compte_collectif) {
                await this.prefetchCompteLabel(t.num_compte_collectif);
            }
            new bootstrap.Modal(document.getElementById("modal_tiers")).show();
        },

        async saveTiers() {
            this.isLoading = true;
            const { data } = await postJson("/accounting/parametres/tiers/save", this.form);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            bootstrap.Modal.getInstance(document.getElementById("modal_tiers"))?.hide();
            this.loadTiers();
        },
    },
});
