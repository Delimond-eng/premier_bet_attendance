import { postJson } from "../../modules/http.js";
import { saisieMixin } from "./saisie-common.js";

new Vue({
    el: "#App",
    mixins: [saisieMixin],
    data() {
        return {
            page: "banque",
            journalId: null,
            csvContent: "",
            journauxBanque: [],
        };
    },

    methods: {
        async initPage() {
            this.journauxBanque = (this.journaux || []).filter((j) => j.type === "banque");
            if (this.journal?.id) this.journalId = this.journal.id;
            else if (this.journauxBanque.length) this.journalId = this.journauxBanque[0].id;
        },

        onFile(ev) {
            const file = ev.target.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.csvContent = e.target.result;
            };
            reader.readAsText(file);
        },

        async importer() {
            if (!this.exercice?.id || !this.journalId) {
                this.error = ["Sélectionnez un journal banque et un exercice courant."];
                return;
            }
            this.isLoading = true;
            const { data } = await postJson("/accounting/saisie/import-releve", {
                journal_id: this.journalId,
                exercice_id: this.exercice.id,
                csv_content: this.csvContent,
            });
            this.isLoading = false;
            if (this.handleResponse(data)) {
                window.location.href = "/accounting/saisie/banque";
            }
        },
    },
});
