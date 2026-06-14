import { get, postJson } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";

export const facturationMixin = {
    mixins: [vuePageMixin],

    data() {
        return {
            meta: null,
            error: null,
            message: null,
            isLoading: false,
        };
    },

    methods: {
        async loadMeta() {
            const { data } = await get("/accounting/facturation/metadata");
            if (data.status === "success") {
                this.meta = data;
            }
        },

        handleResponse(data) {
            if (data.errors) {
                this.error = data.errors;
                return false;
            }
            if (data.message) {
                this.message = data.message;
                this.error = null;
            }
            return true;
        },

        fmt(v) {
            return new Intl.NumberFormat("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v || 0);
        },

        badgeStatut(s) {
            return {
                brouillon: "badge-soft-warning",
                validee: "badge-soft-primary",
                payee: "badge-soft-success",
                annulee: "badge-soft-danger",
                en_validation: "badge-soft-info",
                approuvee: "badge-soft-success",
                rejetee: "badge-soft-danger",
                executee: "badge-soft-dark",
            }[s] || "badge-soft-secondary";
        },

        pdfUrl(factureId) {
            return `/accounting/facturation/factures/${factureId}/pdf`;
        },
    },
};
