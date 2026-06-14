import { postJson } from "../../modules/http.js";
import { exercicesMixin } from "./exercices-common.js";

new Vue({
    el: "#App",
    mixins: [exercicesMixin],
    data() {
        return {
            sourceId: null,
            cibleId: null,
        };
    },

    computed: {
        exercicesClotures() {
            return this.exercices.filter((e) => e.statut === "cloture" || e.statut === "archive");
        },
        exercicesOuverts() {
            return this.exercices.filter((e) => e.statut === "ouvert" || e.statut === "pre_cloture");
        },
        cibleSelectionnee() {
            return this.exercices.find((e) => e.id === this.cibleId) || null;
        },
    },

    methods: {
        initPage() {
            if (this.exercicesClotures.length) {
                this.sourceId = this.exercicesClotures[0].id;
            }
            if (this.exercicesOuverts.length) {
                this.cibleId = this.exerciceCourant?.id || this.exercicesOuverts[0].id;
            }
        },

        async creerSuivant() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/accounting/exercices/ouverture/creer", {
                    exercice_source_id: this.sourceId,
                    est_courant: true,
                });
                if (this.handleResponse(data)) {
                    await this.loadMetadata();
                    if (data.exercice) {
                        this.cibleId = data.exercice.id;
                    }
                }
            } finally {
                this.isLoading = false;
            }
        },

        async genererBilan() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/accounting/exercices/ouverture/bilan", {
                    exercice_id: this.cibleId,
                });
                if (this.handleResponse(data)) {
                    await this.loadMetadata();
                }
            } finally {
                this.isLoading = false;
            }
        },
    },
});
