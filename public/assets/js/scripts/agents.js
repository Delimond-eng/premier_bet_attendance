import { get, post } from "../modules/http.js";
import { initSelect2ForVue } from "../modules/select2.js";

function destroyDatatable(tableEl) {
    const $ = window.$;
    if (!tableEl || !$ || !$.fn || !$.fn.DataTable) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        const dt = $(tableEl).DataTable();
        dt.destroy();
    }
}

function initOrRefreshDatatable(tableEl) {
    const $ = window.$;
    if (!$ || !$.fn || !$.fn.DataTable) return;

    destroyDatatable(tableEl);

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
            groups: [],
            agents: [],
            selectedAgentId: "",
            stats: {
                total: 0,
                actif: 0,
                inactif: 0,
                conges: 0,
            },
            filters: {
                station_id: "",
            },
            createForm: {
                id: "",
                matricule: "",
                fullname: "",
                fonction: "",
                site_id: "",
                groupe_id: "",
                status: "actif",
                photo: null,
                existing_photo_url: "",
                photo_preview_url: "",
            },
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        this.$nextTick(() => {
            initSelect2ForVue(this.$refs.stationSelect, {
                placeholder: "Toutes les stations",
                getValue: () => this.filters.station_id,
                setValue: (v) => {
                    this.filters.station_id = v;
                },
            });
        });

        if (this.$refs.table) {
            this.$refs.table.addEventListener("click", this.onTableClick, true);
        }

        this.loadGroups();
        this.load();
    },

    beforeDestroy() {
        if (this.$refs.table) {
            this.$refs.table.removeEventListener("click", this.onTableClick, true);
        }
    },

    methods: {
        getEmployeeModal() {
            const el = document.getElementById("add_employee");
            if (!el) return null;

            if (window.bootstrap && window.bootstrap.Modal) {
                return window.bootstrap.Modal.getOrCreateInstance(el);
            }

            if (window.$ && window.$.fn && window.$.fn.modal) {
                return {
                    show: () => window.$(el).modal("show"),
                    hide: () => window.$(el).modal("hide"),
                };
            }

            return null;
        },

        openEmployeeModal() {
            const modal = this.getEmployeeModal();
            if (modal) modal.show();
        },

        closeEmployeeModal() {
            const modal = this.getEmployeeModal();
            if (modal) modal.hide();
        },

        onTableClick(e) {
            const target = e?.target;
            if (!target || typeof target.closest !== "function") return;

            const actionEl = target.closest("[data-action]");
            if (!actionEl) return;

            const action = actionEl.dataset.action;
            const id = actionEl.dataset.id;
            if (!action || !id) return;

            const agent = this.agents.find((a) => String(a.id) === String(id));
            if (!agent) return;

            if (action === "edit") this.editAgent(agent);
            else if (action === "remove") this.removeAgent(agent);
        },

        async load() {
            if (this.isLoading) return;
            const stationId =
                (this.$refs.stationSelect && String(this.$refs.stationSelect.value || "")) ||
                String(this.filters.station_id || "");
            this.filters.station_id = stationId;

            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
                const params = new URLSearchParams();
                params.set("per_page", "200");
                if (stationId) params.set("station_id", stationId);

                const { data } = await get(`/agents/data?${params.toString()}`);
                this.agents = data?.agents?.data ?? [];
                this.stats = { ...this.stats, ...(data?.stats ?? {}) };
                this.$nextTick(() => {
                    setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0);
                });
            } catch (e) {
                this.agents = [];
            } finally {
                this.isLoading = false;
            }
        },

        async loadGroups() {
            try {
                const { data } = await get("/rh/groups");
                this.groups = data?.groups ?? [];
            } catch (e) {
                this.groups = [];
            }
        },

        onPhotoChange(e) {
            const file = e?.target?.files?.[0] ?? null;
            this.createForm.photo = file instanceof File ? file : null;

            if (this.createForm.photo_preview_url && this.createForm.photo_preview_url.startsWith("blob:")) {
                try {
                    URL.revokeObjectURL(this.createForm.photo_preview_url);
                } catch (_) {}
            }

            this.createForm.photo_preview_url =
                this.createForm.photo instanceof File ? URL.createObjectURL(this.createForm.photo) : "";
        },

        clearPhoto() {
            if (this.createForm.photo_preview_url && this.createForm.photo_preview_url.startsWith("blob:")) {
                try {
                    URL.revokeObjectURL(this.createForm.photo_preview_url);
                } catch (_) {}
            }
            this.createForm.photo = null;
            this.createForm.photo_preview_url = "";
            // Reset input so selecting the same file again triggers change.
            const input = document.querySelector('#add_employee input[type="file"].image-sign');
            if (input) input.value = "";
        },

        resetCreateForm() {
            this.createForm = {
                id: "",
                matricule: "",
                fullname: "",
                fonction: "",
                site_id: "",
                groupe_id: "",
                status: "actif",
                photo: null,
                existing_photo_url: "",
                photo_preview_url: "",
            };
        },

        editAgent(agent) {
            this.createForm = {
                id: agent.id,
                matricule: agent.matricule ?? "",
                fullname: agent.fullname ?? "",
                fonction: agent.fonction ?? "",
                site_id: agent.site_id ?? "",
                groupe_id: agent.groupe_id ?? "",
                status: agent.status ?? "actif",
                photo: null,
                existing_photo_url: agent.photo ?? "",
                photo_preview_url: "",
            };
            this.openEmployeeModal();
        },

        async removeAgent(agent) {
            const ok = confirm(`Supprimer l'agent "${agent.fullname}" (${agent.matricule}) ?`);
            if (!ok) return;

            this.isLoading = true;
            try {
                const { data } = await post("/table/delete", {
                    table: "agents",
                    id: agent.id,
                });
                if (data?.errors) {
                    alert((data.errors || []).join("\n"));
                    return;
                }
                await this.load();
            } catch (e) {
                alert("Erreur lors de la suppression de l'agent.");
            } finally {
                this.isLoading = false;
            }
        },

        async saveAgent() {
            this.isSaving = true;
            try {
                const formData = new FormData();
                if (this.createForm.id) formData.append("id", String(this.createForm.id));
                formData.append("matricule", this.createForm.matricule || "");
                formData.append("fullname", this.createForm.fullname || "");
                formData.append("fonction", this.createForm.fonction || "");
                formData.append("site_id", this.createForm.site_id || "");
                formData.append("groupe_id", this.createForm.groupe_id || "");
                formData.append("status", this.createForm.status || "actif");
                if (this.createForm.photo) {
                    formData.append("photo", this.createForm.photo);
                }

                const { data } = await post("/agents/store", formData);
                if (data?.errors) {
                    alert((data.errors || []).join("\n"));
                    return;
                }

                this.closeEmployeeModal();
                this.resetCreateForm();
                await this.load();
            } catch (e) {
                alert("Erreur lors de l'enregistrement de l'agent.");
            } finally {
                this.isSaving = false;
            }
        },
    },

    computed: {
        exportPdfUrl() {
            const params = new URLSearchParams();
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/agents/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/agents/export/excel?${params.toString()}`;
        },
    },
});
