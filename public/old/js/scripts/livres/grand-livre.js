import { get } from "../../modules/http.js";
import { compteSelectMixin } from "../../modules/compte-select-mixin.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin, compteSelectMixin],
    data() {
        return {
            vueMode: "general",
            numCompte: "",
            dataCompte: null,
            dataGeneral: null,
        };
    },
    methods: {
        queryParams(extra = {}) {
            const e = { ...extra };
            if (this.vueMode === "compte" && this.numCompte?.trim()) {
                e.num_compte = this.numCompte.trim();
            }
            return livresMixin.methods.queryParams.call(this, e);
        },

        async initPage() {
            const saved = sessionStorage.getItem("livres_gl_mode");
            if (saved === "compte" || saved === "general") {
                this.vueMode = saved;
            }
            if (this.vueMode === "general") {
                await this.loadData();
            }
        },

        setMode(mode) {
            this.vueMode = mode;
            sessionStorage.setItem("livres_gl_mode", mode);
            this.dataCompte = null;
            this.dataGeneral = null;
            if (mode === "general") {
                this.loadData();
            }
        },

        async loadData() {
            if (this.vueMode === "general") {
                await this.loadGeneral();
            } else {
                await this.loadCompte();
            }
        },

        async loadGeneral() {
            this.isLoading = true;
            try {
                const { data } = await get(`/accounting/livres/grand-livre/general/data?${this.queryParams()}`);
                if (!this.handleResponse(data)) {
                    return;
                }
                this.dataGeneral = data.data;
            } finally {
                this.isLoading = false;
            }
        },

        async loadCompte() {
            if (!this.numCompte?.trim()) {
                this.error = ["Indiquez un numéro de compte."];
                return;
            }
            this.isLoading = true;
            try {
                const qs = this.queryParams({ num_compte: this.numCompte.trim() });
                const { data } = await get(`/accounting/livres/grand-livre/data?${qs}`);
                if (!this.handleResponse(data)) {
                    return;
                }
                this.dataCompte = data.data;
            } finally {
                this.isLoading = false;
            }
        },
    },
});
