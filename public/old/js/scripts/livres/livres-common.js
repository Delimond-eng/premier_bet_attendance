import { get, postJson } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";
import { exportMixin } from "../../modules/export-mixin.js";

export const livresMixin = {
    mixins: [vuePageMixin, exportMixin],

    data() {
        return {
            page: window.__LIVRES_PAGE__ || "balance",
            societe: null,
            exercice: null,
            options: { devises: [], devise_affichage: "CDF", mode_conversion: "origine" },
            journaux: [],
            filtres: {
                date_debut: "",
                date_fin: "",
                devise_affichage: "CDF",
                mode_conversion: "origine",
            },
            tauxUsd: 2200,
            dateTaux: null,
            error: null,
            isLoading: false,
            journalId: null,
            exportBase: `/accounting/export/livres/${window.__LIVRES_PAGE__ || "balance"}`,
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
            const { data } = await get("/accounting/livres/metadata");
            if (data.status !== "success") {
                return;
            }
            this.societe = data.societe;
            this.exercice = data.exercice;
            this.options = data.options || this.options;
            this.journaux = data.journaux || [];
            this.tauxUsd = data.taux_usd ?? this.tauxUsd;
            this.dateTaux = data.date_taux;
            this.filtres.date_debut = data.date_debut || this.filtres.date_debut;
            this.filtres.date_fin = data.date_fin || this.filtres.date_fin;
            this.filtres.devise_affichage = this.options.devise_affichage || "CDF";
            this.filtres.mode_conversion = this.options.mode_conversion || "origine";
        },

        queryParams(extra = {}) {
            const p = new URLSearchParams({
                date_debut: this.filtres.date_debut,
                date_fin: this.filtres.date_fin,
                devise_affichage: this.filtres.devise_affichage,
                mode_conversion: this.filtres.mode_conversion,
            });
            if (this.page === "journal" && this.journalId) {
                p.set("journal_id", this.journalId);
            }
            Object.entries(extra).forEach(([k, v]) => {
                if (v !== null && v !== undefined && v !== "") {
                    p.set(k, v);
                }
            });
            return p.toString();
        },

        async onDatesChange() {
            if (typeof this.loadData === "function") {
                await this.loadData();
            }
        },

        async onFiltreChange() {
            await this.savePreferences();
            if (typeof this.loadData === "function") {
                await this.loadData();
            }
        },

        async savePreferences() {
            const { data } = await postJson("/accounting/livres/preferences", {
                devise_affichage: this.filtres.devise_affichage,
                mode_conversion: this.filtres.mode_conversion,
            });
            if (data.status === "success" && data.options) {
                this.options = data.options;
            }
        },

        async saveTauxUsd() {
            if (!this.tauxUsd || this.tauxUsd <= 0) {
                return;
            }
            const date = this.dateTaux || new Date().toISOString().slice(0, 10);
            const { data } = await postJson("/accounting/parametres/taux-change/save", {
                devise_code: "USD",
                date_taux: date,
                taux: this.tauxUsd,
            });
            if (data.errors) {
                this.error = data.errors;
                return;
            }
            if (this.filtres.mode_conversion === "actuel" && typeof this.loadData === "function") {
                await this.loadData();
            }
        },

        handleResponse(data) {
            if (data.errors) {
                this.error = data.errors;
                return false;
            }
            this.error = null;
            return true;
        },

        fmt(v) {
            return new Intl.NumberFormat("fr-FR", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(v || 0);
        },

        suffixDevise(code) {
            const c = (code || "CDF").toUpperCase();
            if (c === "CDF") return "Fr";
            if (c === "USD") return "USD";
            return c;
        },

        fmtMontantDevise(montant, devise) {
            const n = Number(montant) || 0;
            if (n === 0 && montant !== 0) {
                return `0,00 ${this.suffixDevise(devise)}`;
            }
            return `${this.fmt(n)} ${this.suffixDevise(devise)}`;
        },

        fmtDateTime(dt) {
            if (!dt) return "—";
            const s = String(dt).replace("T", " ");
            return s.length >= 19 ? s.slice(0, 19) : s;
        },
    },
};
