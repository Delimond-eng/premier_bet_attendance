import { get, postJson } from "../../modules/http.js";
import { exercicesMixin } from "./exercices-common.js";

new Vue({
    el: "#App",
    mixins: [exercicesMixin],
    data() {
        const now = new Date();
        const pad = (n) => String(n).padStart(2, "0");
        return {
            exerciceId: null,
            moisCloture: `${now.getFullYear()}-${pad(now.getMonth() + 1)}`,
            controles: null,
            controlesMois: null,
            notes: "",
        };
    },

    computed: {
        exercicesActifs() {
            return this.exercices.filter((e) => e.statut === "ouvert" || e.statut === "pre_cloture");
        },
    },

    methods: {
        initPage() {
            this.exerciceId = this.exerciceCourant?.id || this.exercicesActifs[0]?.id || null;
        },

        async lancerControles() {
            if (!this.exerciceId) {
                return;
            }
            this.isLoading = true;
            try {
                const { data } = await get(
                    `/accounting/exercices/controles?exercice_id=${this.exerciceId}`
                );
                if (data.status === "success") {
                    this.controles = data.controles;
                    this.error = null;
                }
            } finally {
                this.isLoading = false;
            }
        },

        async controlesMensuels() {
            if (!this.exerciceId || !this.moisCloture) {
                return;
            }
            const [annee, mois] = this.moisCloture.split("-").map(Number);
            this.isLoading = true;
            try {
                const { data } = await get(
                    `/accounting/exercices/controles-mensuels?exercice_id=${this.exerciceId}&annee=${annee}&mois=${mois}`
                );
                if (data.status === "success") {
                    this.controlesMois = data.controles;
                    this.error = null;
                } else if (data.errors) {
                    this.error = data.errors;
                }
            } finally {
                this.isLoading = false;
            }
        },

        async preCloture() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/accounting/exercices/pre-cloture", {
                    exercice_id: this.exerciceId,
                });
                if (this.handleResponse(data)) {
                    await this.loadMetadata();
                    await this.lancerControles();
                }
            } finally {
                this.isLoading = false;
            }
        },

        async cloturer() {
            if (!confirm("Confirmer la clôture définitive de l'exercice ? Cette opération est irréversible.")) {
                return;
            }
            this.isLoading = true;
            try {
                const { data } = await postJson("/accounting/exercices/cloturer", {
                    exercice_id: this.exerciceId,
                    notes: this.notes || null,
                });
                if (this.handleResponse(data)) {
                    await this.loadMetadata();
                    this.controles = null;
                }
            } finally {
                this.isLoading = false;
            }
        },
    },
});
