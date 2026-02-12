import { get } from "../modules/http.js";
import { initSelect2ForVue } from "../modules/select2.js";

function destroyDatatable(tableEl) {
    const $ = window.$;
    if (!tableEl || !$ || !$.fn || !$.fn.DataTable) return;
    if ($.fn.DataTable.isDataTable(tableEl)) {
        $(tableEl).DataTable().destroy();
    }
}

function initOrRefreshDatatable(tableEl) {
    const $ = window.$;
    if (!tableEl || !$ || !$.fn || !$.fn.DataTable) return;

    destroyDatatable(tableEl);

    $(tableEl).DataTable({
        bFilter: true,
        ordering: true,
        order: [[0, "desc"]],
        info: true,
        pageLength: 25,
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
        const d = `${yyyy}-${mm}-${dd}`;

        return {
            isLoading: false,
            sites: [],
            filters: {
                from: d,
                to: d,
                station_id: "",
            },
            range: { from: "", to: "" },
            rows: [],
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
            try {
                const { data } = await get("/stations/list");
                this.sites = data?.sites ?? [];
            } catch (e) {
                this.sites = [];
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

            await this.load();
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
                if (this.filters.from) params.set("from", this.filters.from);
                if (this.filters.to) params.set("to", this.filters.to);
                if (stationId) params.set("station_id", stationId);
                params.set("per_page", "2000");

                const { data } = await get(`/reports/absences/daily/data?${params.toString()}`);

                this.range = { from: data?.from ?? "", to: data?.to ?? "" };
                const page = data?.absences ?? null;
                this.rows = page?.data ?? [];

                this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0));
            } catch (e) {
                this.range = { from: "", to: "" };
                this.rows = [];
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        exportPdfUrl() {
            const params = new URLSearchParams();
            if (this.filters.from) params.set("from", this.filters.from);
            if (this.filters.to) params.set("to", this.filters.to);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/reports/absences/daily/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            if (this.filters.from) params.set("from", this.filters.from);
            if (this.filters.to) params.set("to", this.filters.to);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/reports/absences/daily/export/excel?${params.toString()}`;
        },
    },
});
