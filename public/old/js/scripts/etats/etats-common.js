import { get } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";
import { exportMixin } from "../../modules/export-mixin.js";

export const etatsMixin = {
    mixins: [vuePageMixin, exportMixin],

    data() {
        return {
            page: window.__ETATS_PAGE__ || "bilan",
            societe: null,
            exercice: null,
            exerciceN1: null,
            exercices: [],
            options: { devises: [], devise_affichage: "CDF", mode_conversion: "origine" },
            filtres: {
                date_arrete: "",
                exercice_id: null,
                devise_affichage: "CDF",
                mode_conversion: "origine",
                avec_n1: true,
            },
            data: null,
            error: null,
            isLoading: false,
            exportBase: `/accounting/export/etats/${window.__ETATS_PAGE__ || "bilan"}`,
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
            const { data } = await get("/accounting/etats/metadata");
            if (data.status !== "success") {
                return;
            }
            this.societe = data.societe;
            this.exercice = data.exercice;
            this.exerciceN1 = data.exercice_n1;
            this.exercices = data.exercices || [];
            this.options = data.options || this.options;
            this.filtres.date_arrete = data.date_arrete || this.filtres.date_arrete;
            this.filtres.exercice_id = data.exercice?.id || null;
            this.filtres.devise_affichage = this.options.devise_affichage || "CDF";
            this.filtres.mode_conversion = this.options.mode_conversion || "origine";
        },

        queryParams() {
            return new URLSearchParams({
                date_arrete: this.filtres.date_arrete,
                exercice_id: this.filtres.exercice_id || "",
                devise_affichage: this.filtres.devise_affichage,
                mode_conversion: this.filtres.mode_conversion,
                avec_n1: this.filtres.avec_n1 ? "1" : "0",
            }).toString();
        },

        onExerciceChange() {
            const ex = this.exercices.find((e) => e.id === this.filtres.exercice_id);
            if (ex?.date_fin) {
                this.filtres.date_arrete = String(ex.date_fin).slice(0, 10);
            }
            this.loadData();
        },

        onDatesChange() {
            this.loadData();
        },

        onFiltreChange() {
            this.loadData();
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
            if (v === null || v === undefined || v === "") {
                return "—";
            }
            return new Intl.NumberFormat("fr-FR", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(v) || 0);
        },

        fmtMontant(v) {
            return this.fmt(v);
        },

        isTitre(l) {
            return (l.type || "") === "titre";
        },

        isTotal(l) {
            return (l.type || "") === "total";
        },

        rowClass(l) {
            if (this.isTitre(l)) return "table-secondary fw-bold";
            if (this.isTotal(l)) return "table-light fw-bold";
            return "";
        },

        exportUrl(format) {
            return `${this.exportBase}/${format}?${this.queryParams()}`;
        },
    },
};
