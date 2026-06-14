import { get } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";
import { exportMixin } from "../../modules/export-mixin.js";

export const saisieMixin = {
    mixins: [vuePageMixin, exportMixin],

    data() {
        return {
            page: window.__SAISIE_PAGE__ || "nouvelle",
            exercice: null,
            journal: null,
            journaux: [],
            error: null,
            message: null,
            warnings: [],
            isLoading: false,
            filtres: {
                date_debut: "",
                date_fin: "",
            },
            exportBase: "/accounting/export/saisie",
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
        async loadMetadata(journalId = null) {
            const params = new URLSearchParams({ page: this.page });
            if (journalId) params.set("journal_id", journalId);
            const { data } = await get(`/accounting/saisie/metadata?${params}`);
            if (data.status === "success") {
                this.exercice = data.exercice;
                this.journal = data.journal;
                this.journaux = data.journaux || [];
                this.multiDevise = !!data.multi_devise;
                this.devisePrincipale = data.devise_principale;
                this.template = data.template || [];
                if (data.exercice) {
                    this.filtres.date_debut = this.filtres.date_debut || data.exercice.date_debut?.slice?.(0, 10) || data.exercice.date_debut;
                    this.filtres.date_fin = this.filtres.date_fin || data.exercice.date_fin?.slice?.(0, 10) || data.exercice.date_fin;
                }
            }
            return data;
        },

        queryParams(extra = {}) {
            const p = new URLSearchParams({ page: this.page });
            if (this.filtres.date_debut) {
                p.set("date_debut", this.filtres.date_debut);
            }
            if (this.filtres.date_fin) {
                p.set("date_fin", this.filtres.date_fin);
            }
            if (this.search) {
                p.set("search", this.search);
            }
            if (this.filtreStatut) {
                p.set("statut", this.filtreStatut);
            }
            Object.entries(extra).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== "") {
                    p.set(k, v);
                }
            });
            return p.toString();
        },

        handleResponse(data) {
            if (data.errors) {
                this.error = data.errors;
                this.warnings = [];
                return false;
            }
            this.warnings = data.warnings || [];
            if (data.message) {
                this.message = data.message;
                this.error = null;
            }
            return true;
        },

        formatMontant(v) {
            return new Intl.NumberFormat("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v || 0);
        },

        suffixDevise(code) {
            const c = (code || "CDF").toUpperCase();
            if (c === "CDF") return "Fr";
            if (c === "USD") return "USD";
            return c;
        },

        formatMontantDevise(montant, devise) {
            return `${this.formatMontant(montant)} ${this.suffixDevise(devise)}`;
        },

        formatDateTime(dt) {
            if (!dt) return "—";
            const s = String(dt).replace("T", " ");
            return s.length >= 19 ? s.slice(0, 19) : s;
        },

        badgeStatut(s) {
            return {
                brouillon: "badge-soft-warning",
                validee: "badge-soft-success",
                extournee: "badge-soft-danger",
                simulee: "badge-soft-secondary",
            }[s] || "badge-soft-secondary";
        },
    },
};
