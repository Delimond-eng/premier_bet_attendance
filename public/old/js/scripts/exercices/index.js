import { postJson } from "../../modules/http.js";
import { exercicesMixin } from "./exercices-common.js";

new Vue({
    el: "#App",
    mixins: [exercicesMixin],
    data() {
        return {
            showForm: false,
            form: this.emptyForm(),
        };
    },

    methods: {
        emptyForm() {
            const y = new Date().getFullYear();
            return {
                id: null,
                libelle: `Exercice ${y}`,
                annee: y,
                date_debut: `${y}-01-01`,
                date_fin: `${y}-12-31`,
                statut: "ouvert",
                est_courant: false,
            };
        },

        async saveExercice() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/accounting/exercices/exercice/save", this.form);
                if (this.handleResponse(data)) {
                    this.showForm = false;
                    this.form = this.emptyForm();
                    await this.loadMetadata();
                }
            } finally {
                this.isLoading = false;
            }
        },

        async setCourant(id) {
            this.isLoading = true;
            try {
                const { data } = await postJson("/accounting/exercices/exercice/courant", { exercice_id: id });
                if (this.handleResponse(data)) {
                    await this.loadMetadata();
                }
            } finally {
                this.isLoading = false;
            }
        },
    },
});
