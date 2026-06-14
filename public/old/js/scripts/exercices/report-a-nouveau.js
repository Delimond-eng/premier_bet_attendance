import { postJson } from "../../modules/http.js";
import { exercicesMixin } from "./exercices-common.js";

new Vue({
    el: "#App",
    mixins: [exercicesMixin],
    data() {
        return {
            exerciceId: null,
        };
    },

    computed: {
        exercicesOuverts() {
            return this.exercices.filter((e) => e.statut === "ouvert" || e.statut === "pre_cloture");
        },
        selection() {
            return this.exercices.find((e) => e.id === this.exerciceId) || null;
        },
    },

    methods: {
        initPage() {
            this.exerciceId = this.exerciceCourant?.id || this.exercicesOuverts[0]?.id || null;
        },

        async genererRan() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/accounting/exercices/report-a-nouveau/generer", {
                    exercice_id: this.exerciceId,
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
