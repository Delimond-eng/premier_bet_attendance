import { get, postJson } from "../../modules/http.js";
import { parametresMixin } from "./parametres-common.js";

let searchTimer = null;

new Vue({
    el: "#App",
    mixins: [parametresMixin],
    data() {
        return {
            comptes: [],
            classes: {},
            search: "",
            filtreClasse: null,
            form: this.emptyForm(),
            exportBase: "/accounting/export/parametres/plan-comptable",
        };
    },

    methods: {
        queryParams() {
            const p = new URLSearchParams();
            if (this.search) {
                p.set("search", this.search);
            }
            if (this.filtreClasse) {
                p.set("classe", this.filtreClasse);
            }
            return p.toString();
        },

        async initPage() {
            await this.loadComptes();
        },

        async onSocieteChanged() {
            await this.loadComptes();
        },

        emptyForm() {
            return {
                id: null,
                num_compte: "",
                libelle: "",
                classe: 4,
                type_compte_detail: "",
                est_compte_tiers: false,
                est_rapprochable: false,
            };
        },

        debounceSearch() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => this.loadComptes(), 350);
        },

        async loadComptes() {
            this.isLoading = true;
            const params = new URLSearchParams();
            if (this.search) params.set("search", this.search);
            if (this.filtreClasse) params.set("classe", this.filtreClasse);
            try {
                const { data } = await get(
                    `/accounting/parametres/plan-comptable/all?${params.toString()}`
                );
                if (data.status === "success") {
                    this.comptes = data.comptes || [];
                    this.classes = data.classes || {};
                }
            } finally {
                this.isLoading = false;
            }
        },

        openForm() {
            this.form = this.emptyForm();
            if (this.filtreClasse) this.form.classe = this.filtreClasse;
            new bootstrap.Modal(document.getElementById("modal_compte")).show();
        },

        editCompte(c) {
            this.form = {
                id: c.id,
                num_compte: c.num_compte,
                libelle: c.libelle,
                classe: c.classe,
                type_compte_detail: c.type_compte_detail || "",
                est_compte_tiers: !!c.est_compte_tiers,
                est_rapprochable: !!c.est_rapprochable,
            };
            new bootstrap.Modal(document.getElementById("modal_compte")).show();
        },

        async saveCompte() {
            this.isLoading = true;
            const { data } = await postJson("/accounting/parametres/plan-comptable/save", this.form);
            this.isLoading = false;
            if (!this.handleResponse(data)) return;
            bootstrap.Modal.getInstance(document.getElementById("modal_compte"))?.hide();
            this.loadComptes();
        },
    },
});
