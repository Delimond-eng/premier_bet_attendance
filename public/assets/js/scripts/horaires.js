import { get, postJson } from "../modules/http.js";
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
            sites: [],
            horaires: [],
            filters: {
                site_id: "",
            },
            form: {
                id: "",
                libelle: "",
                started_at: "",
                ended_at: "",
                tolerence_minutes: 15,
                site_id: "",
            },
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.init();
    },

    methods: {
        async init() {
            await this.loadSites();
            await this.load();
        },

        async loadSites() {
            const { data } = await get("/stations/list");
            this.sites = data?.sites ?? [];
            this.$nextTick(() => {
                initSelect2ForVue(this.$refs.stationFilterSelect, {
                    placeholder: "Toutes les stations",
                    getValue: () => this.filters.site_id,
                    setValue: (v) => {
                        this.filters.site_id = v;
                    },
                });
            });
        },

        stationName(id) {
            const s = this.sites.find((x) => String(x.id) === String(id));
            return s ? s.name : "--";
        },

        async load() {
            if (this.isLoading) return;
            this.isLoading = true;
            try {
                const siteId =
                    (this.$refs.stationFilterSelect &&
                        String(this.$refs.stationFilterSelect.value || "")) ||
                    String(this.filters.site_id || "");
                this.filters.site_id = siteId;

                destroyDatatable(this.$refs.table);

                const params = new URLSearchParams();
                if (siteId) params.set("site_id", siteId);
                const { data } = await get(`/rh/horaires?${params.toString()}`);
                this.horaires = data?.horaires ?? [];
                this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0));
            } catch (e) {
                this.horaires = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(h) {
            this.form = {
                id: h.id,
                libelle: h.libelle ?? "",
                started_at: h.started_at ?? "",
                ended_at: h.ended_at ?? "",
                tolerence_minutes: h.tolerence_minutes ?? 15,
                site_id: h.site_id ?? "",
            };
            window.$("#add_horaire").modal("show");
        },

        reset() {
            this.form = {
                id: "",
                libelle: "",
                started_at: "",
                ended_at: "",
                tolerence_minutes: 15,
                site_id: "",
            };
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/horaire/store", this.form);
                if (data?.errors) return;
                window.$("#add_horaire").modal("hide");
                this.reset();
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        async remove(h) {
            const ok = confirm(`Supprimer l'horaire "${h.libelle}" ?`);
            if (!ok) return;

            this.isLoading = true;
            try {
                const { data } = await postJson("/table/delete", {
                    table: "presence_horaires",
                    id: h.id,
                });
                if (data?.errors) return;
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        exportPdfUrl() {
            const params = new URLSearchParams();
            if (this.filters.site_id) params.set("station_id", String(this.filters.site_id));
            return `/rh/horaires/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            if (this.filters.site_id) params.set("station_id", String(this.filters.site_id));
            return `/rh/horaires/export/excel?${params.toString()}`;
        },
    },

    computed: {
        exportPdfUrl() {
            const params = new URLSearchParams();
            if (this.filters.site_id) params.set("station_id", String(this.filters.site_id));
            return `/rh/horaires/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            if (this.filters.site_id) params.set("station_id", String(this.filters.site_id));
            return `/rh/horaires/export/excel?${params.toString()}`;
        },
    },
});
