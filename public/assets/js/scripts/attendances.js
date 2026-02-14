import { get } from "../modules/http.js";
import { initSelect2ForVue } from "../modules/select2.js";

function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

function destroyDatatable(tableEl) {
    const $ = window.$;
    if (!tableEl || !$ || !$.fn || !$.fn.DataTable) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        const dt = $(tableEl).DataTable();
        // Keep the table element in DOM (Vue owns rendering).
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
        order: [[4, "desc"]],
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

        const qDate = getQueryParam("date");
        const qStation = getQueryParam("station_id");

        return {
            isLoading: false,
            sites: [],
            presences: [],
            filters: {
                date: qDate || `${yyyy}-${mm}-${dd}`,
                station_id: qStation || "",
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
                initSelect2ForVue(this.$refs.stationSelect, {
                    placeholder: "Toutes les stations",
                    getValue: () => this.filters.station_id,
                    setValue: (v) => {
                        this.filters.station_id = v;
                    },
                });
            });
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
                if (this.filters.date) params.set("date", this.filters.date);
                if (stationId) params.set("station_id", stationId);

                const { data } = await get(`/presences/data?${params.toString()}`);
                let presences = data?.presences ?? [];
                if (stationId) {
                    presences = presences.filter(
                        (p) => String(p?.site_id ?? "") === String(stationId)
                    );
                }
                this.presences = presences;
                this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0));
            } catch (e) {
                this.presences = [];
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        exportPdfUrl() {
            const params = new URLSearchParams();
            if (this.filters.date) params.set("date", this.filters.date);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/presences/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            if (this.filters.date) params.set("date", this.filters.date);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/presences/export/excel?${params.toString()}`;
        },
    },
});
