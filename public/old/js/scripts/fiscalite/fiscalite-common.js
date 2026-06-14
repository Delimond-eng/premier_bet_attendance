import { get } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";
import { exportMixin } from "../../modules/export-mixin.js";

export const fiscaliteMixin = {
    mixins: [vuePageMixin, exportMixin],

    data() {
        return {
            page: window.__FISCALITE_PAGE__ || "tva-collectee",
            societe: null,
            exercice: null,
            exercices: [],
            options: { devises: [], devise_affichage: "CDF", mode_conversion: "origine" },
            config: { taux_tva: 18, taux_is: 30 },
            filtres: {
                date_debut: "",
                date_fin: "",
                exercice_id: null,
                devise_affichage: "CDF",
                mode_conversion: "origine",
            },
            data: null,
            error: null,
            isLoading: false,
            exportBase: `/accounting/export/fiscalite/${window.__FISCALITE_PAGE__ || "tva-collectee"}`,
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
            const { data } = await get("/accounting/fiscalite/metadata");
            if (data.status !== "success") {
                return;
            }
            this.societe = data.societe;
            this.exercice = data.exercice;
            this.exercices = data.exercices || [];
            this.options = data.options || this.options;
            this.config = data.config || this.config;
            this.filtres.date_debut = data.date_debut || "";
            this.filtres.date_fin = data.date_fin || "";
            this.filtres.exercice_id = data.exercice?.id || null;
            this.filtres.devise_affichage = this.options.devise_affichage || "CDF";
            this.filtres.mode_conversion = this.options.mode_conversion || "origine";
        },

        queryParams() {
            return new URLSearchParams({
                date_debut: this.filtres.date_debut,
                date_fin: this.filtres.date_fin,
                exercice_id: this.filtres.exercice_id || "",
                devise_affichage: this.filtres.devise_affichage,
                mode_conversion: this.filtres.mode_conversion,
            }).toString();
        },

        onExerciceChange() {
            const ex = this.exercices.find((e) => e.id === this.filtres.exercice_id);
            if (ex?.date_debut) {
                this.filtres.date_debut = String(ex.date_debut).slice(0, 10);
            }
            if (ex?.date_fin) {
                this.filtres.date_fin = String(ex.date_fin).slice(0, 10);
            }
            this.exercice = ex || this.exercice;
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

        statutLabel(s) {
            const map = {
                brouillon: "Brouillon",
                a_declarer: "À déclarer",
                deposee: "Déposée",
                validee: "Validée",
            };
            return map[s] || s;
        },

        statutClass(s) {
            if (s === "deposee" || s === "validee") return "bg-success";
            if (s === "brouillon") return "bg-warning text-dark";
            return "bg-secondary";
        },
    },
};
