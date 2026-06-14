import { get, postJson } from "../../modules/http.js";
import { vuePageMixin } from "../../modules/vue-page-mixin.js";
import { exportMixin } from "../../modules/export-mixin.js";

export const parametresMixin = {
    mixins: [vuePageMixin, exportMixin],

    data() {
        return {
            societes: [],
            societeId: null,
            societe: null,
            exerciceCourant: null,
            error: null,
            message: null,
            isLoading: false,
        };
    },

    async mounted() {
        await this.bootPage(async () => {
            await this.loadContext();
            if (typeof this.initPage === "function") {
                await this.initPage();
            }
        });
    },

    methods: {
        async loadContext() {
            const { data } = await get("/accounting/parametres/context");
            if (data.status === "success") {
                this.societes = data.societes || [];
                this.societeId = data.societe_id;
                this.societe = data.societe;
                this.exerciceCourant = data.exercice_courant;
            }
        },

        async changeSociete() {
            const { data } = await postJson("/accounting/parametres/societe/select", {
                societe_id: this.societeId,
            });
            if (!this.handleResponse(data)) {
                return;
            }
            await this.loadContext();
            if (typeof this.onSocieteChanged === "function") {
                await this.onSocieteChanged();
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
    },
};
