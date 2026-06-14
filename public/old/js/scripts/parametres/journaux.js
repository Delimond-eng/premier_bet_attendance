import { get, postJson } from "../../modules/http.js";
import { compteSelectMixin } from "../../modules/compte-select-mixin.js";
import { parametresMixin } from "./parametres-common.js";

new Vue({
    el: "#App",
    mixins: [parametresMixin, compteSelectMixin],
    data() {
        return {
            journaux: [],
            form: this.emptyForm(),
            exportBase: "/accounting/export/parametres/journaux",
        };
    },

    methods: {
        queryParams() {
            return "";
        },

        async initPage() {
            await this.loadJournaux();
        },

        async onSocieteChanged() {
            await this.loadJournaux();
        },

        emptyForm() {
            return {
                id: null,
                code: "",
                libelle: "",
                type: "operations_diverses",
                compte_contrepartie: "",
                prefixe_piece: "",
                format_numerotation: "annuel",
                padding_numero: 5,
                saisie_tiers_obligatoire: false,
                actif: true,
                ordre_affichage: 10,
            };
        },

        async loadJournaux() {
            this.isLoading = true;
            try {
                const { data } = await get("/accounting/parametres/journaux/all");
                if (data.status === "success") {
                    this.journaux = data.journaux || [];
                }
            } finally {
                this.isLoading = false;
            }
        },

        openForm() {
            this.form = this.emptyForm();
            new bootstrap.Modal(document.getElementById("modal_journal")).show();
        },

        async editJournal(j) {
            this.form = { ...j };
            if (j.compte_contrepartie) {
                await this.prefetchCompteLabel(j.compte_contrepartie);
            }
            new bootstrap.Modal(document.getElementById("modal_journal")).show();
        },

        async saveJournal() {
            this.isLoading = true;
            const { data } = await postJson("/accounting/parametres/journaux/save", this.form);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            bootstrap.Modal.getInstance(document.getElementById("modal_journal"))?.hide();
            this.loadJournaux();
        },
    },
});
