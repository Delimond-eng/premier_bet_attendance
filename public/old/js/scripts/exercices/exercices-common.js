import { get, postJson } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";

export const exercicesMixin = {
    mixins: [vuePageMixin],

    data() {
        return {
            societe: null,
            exerciceCourant: null,
            exercices: [],
            error: null,
            message: null,
            isLoading: false,
        };
    },

    async mounted() {
        await this.bootPage(async () => {
            await this.loadMetadata();
            if (typeof this.initPage === "function") {
                await this.initPage();
            }
        });
    },

    methods: {
        async loadMetadata() {
            const { data } = await get("/accounting/exercices/metadata");
            if (data.status !== "success") {
                return;
            }
            this.societe = data.societe;
            this.exerciceCourant = data.exercice_courant;
            this.exercices = data.exercices || [];
        },

        handleResponse(data) {
            if (data.errors) {
                this.error = data.errors;
                this.message = null;
                return false;
            }
            this.error = null;
            if (data.message) {
                this.message = data.message;
            }
            if (data.exercices) {
                this.exercices = data.exercices;
            }
            if (data.exercice) {
                const idx = this.exercices.findIndex((e) => e.id === data.exercice.id);
                if (idx >= 0) {
                    this.$set(this.exercices, idx, data.exercice);
                } else {
                    this.exercices.unshift(data.exercice);
                }
                if (data.exercice.est_courant) {
                    this.exerciceCourant = data.exercice;
                }
            }
            return true;
        },

        statutLabel(s) {
            return { ouvert: "Ouvert", pre_cloture: "Pré-clôture", cloture: "Clôturé", archive: "Archivé" }[s] || s;
        },

        statutClass(s) {
            return {
                ouvert: "bg-success",
                pre_cloture: "bg-warning text-dark",
                cloture: "bg-secondary",
                archive: "bg-dark",
            }[s] || "bg-light text-dark";
        },

        fmt(v) {
            if (v === null || v === undefined) {
                return "—";
            }
            return new Intl.NumberFormat("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(v) || 0);
        },
    },
};
