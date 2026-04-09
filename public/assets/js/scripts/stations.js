import { get, post, postJson } from "../modules/http.js";

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
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, "0");
        const dd = String(today.getDate()).padStart(2, "0");

        return {
            isLoading: false,
            isImporting: false,
            sites: [],
            filters: {
                date: `${yyyy}-${mm}-${dd}`,
            },
            codeManuallyEdited: false,
            form: {
                id: "",
                name: "",
                type: "",
                code: "",
                adresse: "",
            },
            importForm: {
                file: null,
            },
            qrExport: {
                format: 'a4',
                cols: 3,
                orientation: 'landscape',
                selectedIds: [],
                search: '',
            }
        };
    },

    computed: {
        filteredStationsForSelection() {
            const search = String(this.qrExport.search || "").toLowerCase().trim();
            if (!search) return this.sites;
            return this.sites.filter(s =>
                String(s.name || "").toLowerCase().includes(search) ||
                String(s.code || "").toLowerCase().includes(search)
            );
        }
    },

    watch: {
        "form.name": function () {
            this.ensureAutoCode();
        },
        "qrExport.format": function (newFormat) {
            if (newFormat === 'a4') {
                this.qrExport.cols = 3;
            } else if (newFormat === 'a3') {
                this.qrExport.cols = 4;
            } else if (newFormat === 'a2') {
                this.qrExport.cols = 8;
            }
        }
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        if (this.$refs.table) {
            this.$refs.table.addEventListener("click", this.onTableClick, true);
        }
        this.resetImportForm();
        this.load();
    },

    beforeDestroy() {
        if (this.$refs.table) {
            this.$refs.table.removeEventListener("click", this.onTableClick, true);
        }
    },

    methods: {
        normalizeName(name) {
            const s = String(name || "").trim();
            if (!s) return "";
            return s
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^a-zA-Z0-9 ]/g, " ")
                .replace(/\s+/g, " ")
                .trim();
        },

        buildCodeFromName(name) {
            const normalized = this.normalizeName(name);
            if (!normalized) return "";

            const parts = normalized.split(" ").filter(Boolean);
            let prefix = "";
            if (parts.length >= 2) {
                prefix = (parts[0][0] + parts[1][0]).toUpperCase();
            } else {
                prefix = parts[0].slice(0, 2).toUpperCase();
            }

            const rand = String(Math.floor(Math.random() * 9000) + 1000);
            return `${prefix}${rand}`;
        },

        ensureAutoCode() {
            if (this.form.id) return; // do not change code on edit
            if (this.codeManuallyEdited) return;
            if (String(this.form.code || "").trim() !== "") return;
            const code = this.buildCodeFromName(this.form.name);
            if (code) this.form.code = code;
        },

        regenerateCode() {
            if (this.form.id) return;
            this.codeManuallyEdited = false;
            this.form.code = "";
            this.ensureAutoCode();
        },

        onCodeInput() {
            // If user edits code, stop auto generation.
            this.codeManuallyEdited = true;
        },

        getStationModal() {
            const el = document.getElementById("add_station");
            if (!el) return null;

            if (window.bootstrap && window.bootstrap.Modal) {
                return window.bootstrap.Modal.getOrCreateInstance(el);
            }

            // Fallback for older bootstrap builds (if any)
            if (window.$ && window.$.fn && window.$.fn.modal) {
                return {
                    show: () => window.$(el).modal("show"),
                    hide: () => window.$(el).modal("hide"),
                };
            }

            return null;
        },

        openModal() {
            const modal = this.getStationModal();
            if (modal) modal.show();
        },

        closeModal() {
            const modal = this.getStationModal();
            if (modal) modal.hide();
        },

        getImportModal() {
            const el = document.getElementById("import_stations_excel");
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

            const site = this.sites.find((s) => String(s.id) === String(id));
            if (!site) return;

            if (action === "edit") this.edit(site);
            else if (action === "remove") this.remove(site);
        },

        async load() {
            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
                const params = new URLSearchParams();
                if (this.filters.date) params.set("date", this.filters.date);
                const { data } = await get(`/stations/list?${params.toString()}`);
                this.sites = data?.sites ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.sites = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(site) {
            this.form = {
                id: site.id,
                name: site.name ?? "",
                type: site.type ?? "",
                code: site.code ?? "",
                adresse: site.adresse ?? "",
            };
            this.codeManuallyEdited = true;
            this.openModal();
        },

        reset() {
            this.form = {
                id: "",
                name: "",
                type: "",
                code: "",
                adresse: "",
            };
            this.codeManuallyEdited = false;
        },

        resetImportForm() {
            this.importForm = {
                file: null,
            };

            if (this.$refs.importStationsFileInput) {
                this.$refs.importStationsFileInput.value = "";
            }
        },

        onImportStationsFileChange(e) {
            const file = e?.target?.files?.[0] ?? null;
            this.importForm.file = file instanceof File ? file : null;
        },

        async save() {
            this.isLoading = true;
            try {
                this.ensureAutoCode();
                const { data } = await postJson("/stations/store", this.form);
                if (data?.errors) return;
                this.closeModal();
                this.reset();
                this.isLoading = false;
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        async importStationsExcel() {
            if (this.isImporting) return;

            if (!(this.importForm.file instanceof File)) {
                alert("Veuillez selectionner un fichier Excel.");
                return;
            }

            this.isImporting = true;
            try {
                const formData = new FormData();
                formData.append("file", this.importForm.file);

                const { data, status } = await post("/stations/import/excel", formData);
                if (status >= 400 || data?.status !== "success") {
                    alert((data?.errors || ["Erreur lors de l'import."]).join("\n"));
                    return;
                }

                const stats = data?.stats || {};
                let summary = [
                    "Import termine.",
                    `Creees: ${stats.created ?? 0}`,
                    `Mises a jour: ${stats.updated ?? 0}`,
                    `Ignorees: ${stats.skipped ?? 0}`,
                ].join("\n");

                if (Array.isArray(data?.errors) && data.errors.length > 0) {
                    summary += `\n\nErreurs detectees (${data.errors.length})`;
                }

                alert(summary);
                this.closeImportModal();
                this.resetImportForm();
                await this.load();
            } catch (e) {
                alert("Erreur lors de l'import des stations.");
            } finally {
                this.isImporting = false;
            }
        },

        async remove(site) {
            const ok = confirm(`Supprimer la station "${site.name}" ?`);
            if (!ok) return;

            this.isLoading = true;
            try {
                const { data } = await postJson("/table/delete", {
                    table: "sites",
                    id: site.id,
                });
                if (data?.errors) return;
                this.isLoading = false;
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        downloadQrcodes() {
            const params = new URLSearchParams({
                format: this.qrExport.format,
                cols: this.qrExport.cols,
                orientation: this.qrExport.orientation
            });

            if (this.qrExport.selectedIds.length > 0) {
                params.set('ids', this.qrExport.selectedIds.join(','));
            }

            window.location.href = `/stations/qrcodes?${params.toString()}`;
        },

        openSelectionModal() {
            const el = document.getElementById("selection_stations_modal");
            if (el && window.bootstrap) {
                const modal = window.bootstrap.Modal.getOrCreateInstance(el);
                modal.show();
            }
        },

        toggleStationSelection(id) {
            const idx = this.qrExport.selectedIds.indexOf(id);
            if (idx > -1) {
                this.qrExport.selectedIds.splice(idx, 1);
            } else {
                this.qrExport.selectedIds.push(id);
            }
        },

        selectAllStationsForExport() {
            this.qrExport.selectedIds = this.sites.map(s => s.id);
        }
    },
});
