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
            isImporting: false,
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
            importForm: {
                station_id: "",
                groupe_id: "",
                file: null,
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

        this.resetImportForm();
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

        getImportModal() {
            const el = document.getElementById("import_agents_excel");
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

        openImportModal() {
            const modal = this.getImportModal();
            if (modal) modal.show();
        },

        closeImportModal() {
            const modal = this.getImportModal();
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

        async load(force = false) {
            if (this.isLoading && !force) return;
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

        resetImportForm() {
            const defaultStation = String(this.filters.station_id || this.sites?.[0]?.id || "");
            this.importForm = {
                station_id: defaultStation,
                groupe_id: "",
                file: null,
            };
            if (this.$refs.importFileInput) {
                this.$refs.importFileInput.value = "";
            }
        },

        onImportFileChange(e) {
            const file = e?.target?.files?.[0] ?? null;
            this.importForm.file = file instanceof File ? file : null;
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
                this.isLoading = false;
                await this.load(true);
            } catch (e) {
                alert("Erreur lors de la suppression de l'agent.");
            } finally {
                this.isLoading = false;
            }
        },

        async importAgentsExcel() {
            if (this.isImporting) return;

            if (!this.importForm.station_id) {
                alert("Veuillez selectionner une station.");
                return;
            }

            if (!this.importForm.groupe_id) {
                alert("Veuillez selectionner un groupe d'horaire.");
                return;
            }

            if (!(this.importForm.file instanceof File)) {
                alert("Veuillez selectionner un fichier Excel.");
                return;
            }

            this.isImporting = true;
            try {
                const formData = new FormData();
                formData.append("station_id", String(this.importForm.station_id));
                formData.append("groupe_id", String(this.importForm.groupe_id));
                formData.append("file", this.importForm.file);

                const { data, status } = await post("/agents/import/excel", formData);
                if (status >= 400 || data?.status !== "success") {
                    alert((data?.errors || ["Erreur lors de l'import."]).join("\n"));
                    return;
                }

                const stats = data?.stats || {};
                let summary = [
                    "Import termine.",
                    `Crees: ${stats.created ?? 0}`,
                    `Mis a jour: ${stats.updated ?? 0}`,
                    `Ignores: ${stats.skipped ?? 0}`,
                ].join("\n");

                if (Array.isArray(data?.errors) && data.errors.length > 0) {
                    const preview = data.errors.slice(0, 5).join("\n");
                    summary += `\n\nErreurs detectees (${data.errors.length}) :\n${preview}`;
                }

                alert(summary);
                this.closeImportModal();
                this.resetImportForm();
                await this.load(true);
            } catch (e) {
                alert("Erreur lors de l'import Excel.");
            } finally {
                this.isImporting = false;
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
        filteredGroups() {
            if (!this.createForm.site_id) {
                return this.groups;
            }
            const stationId = String(this.createForm.site_id);
            return this.groups.filter(
                (g) => String(g?.horaire?.site_id ?? "") === stationId
            );
        },
        filteredImportGroups() {
            const stationId = String(this.importForm.station_id || "");
            if (!stationId) {
                return this.groups;
            }

            return this.groups.filter((g) => {
                const groupStationId = g?.horaire?.site_id;
                if (groupStationId === null || groupStationId === undefined || groupStationId === "") {
                    return true;
                }

                return String(groupStationId) === stationId;
            });
        },
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

    watch: {
        "importForm.station_id"() {
            if (!this.importForm.groupe_id) return;
            const keep = this.filteredImportGroups.some(
                (g) => String(g.id) === String(this.importForm.groupe_id)
            );
            if (!keep) {
                this.importForm.groupe_id = "";
            }
        },
        "createForm.site_id"(value) {
            if (!value || !this.createForm.groupe_id) return;
            const keep = this.groups.some(
                (g) =>
                    String(g.id) === String(this.createForm.groupe_id) &&
                    String(g?.horaire?.site_id ?? "") === String(value)
            );
            if (!keep) {
                this.createForm.groupe_id = "";
            }
        },
    },
});
