import { get, post } from "../modules/http.js";

function initOrRefreshDatatable(tableEl) {
    const $ = window.$;
    if (!$ || !$.fn || !$.fn.DataTable) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        $(tableEl).DataTable().destroy();
    }

    $(tableEl).DataTable({
        bFilter: true,
        ordering: true,
        info: true,
        language: {
            search: " ",
            sLengthMenu: "Lignes par page _MENU_",
            searchPlaceholder: "Rechercher",
            info: "Affichage _START_ - _END_ sur _TOTAL_",
            paginate: {
                next: '<i class="ti ti-chevron-right"></i>',
                previous: '<i class="ti ti-chevron-left"></i> ',
            },
        },
    });
}

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            isSaving: false,
            sites: Array.isArray(window.__SITES__) ? window.__SITES__ : [],
            agents: [],
            stats: {
                total: 0,
                actif: 0,
                inactif: 0,
                conges: 0,
            },
            createForm: {
                matricule: "",
                fullname: "",
                site_id: "",
                status: "actif",
                photo: null,
            },
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        this.load();
    },

    methods: {
        async load() {
            this.isLoading = true;
            try {
                const { data } = await get("/agents/data?per_page=200");
                this.agents = data?.agents?.data ?? [];
                this.stats = { ...this.stats, ...(data?.stats ?? {}) };
                this.$nextTick(() => {
                    initOrRefreshDatatable(this.$refs.table);
                });
            } catch (e) {
                this.agents = [];
            } finally {
                this.isLoading = false;
            }
        },

        onPhotoChange(e) {
            const file = e?.target?.files?.[0] ?? null;
            this.createForm.photo = file instanceof File ? file : null;
        },

        resetCreateForm() {
            this.createForm = {
                matricule: "",
                fullname: "",
                site_id: "",
                status: "actif",
                photo: null,
            };
        },

        async saveAgent() {
            this.isSaving = true;
            try {
                const formData = new FormData();
                formData.append("matricule", this.createForm.matricule || "");
                formData.append("fullname", this.createForm.fullname || "");
                formData.append("site_id", this.createForm.site_id || "");
                formData.append("status", this.createForm.status || "actif");
                if (this.createForm.photo) {
                    formData.append("photo", this.createForm.photo);
                }

                const { data } = await post("/agents/store", formData);
                if (data?.errors) {
                    alert((data.errors || []).join("\n"));
                    return;
                }

                window.$?.("#add_employee")?.modal?.("hide");
                this.resetCreateForm();
                await this.load();
            } catch (e) {
                alert("Erreur lors de l'enregistrement de l'agent.");
            } finally {
                this.isSaving = false;
            }
        },
    },
});
