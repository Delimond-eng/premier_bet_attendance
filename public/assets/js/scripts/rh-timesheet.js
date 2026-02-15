import { get } from "../modules/http.js";
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
        order: [[0, "desc"]],
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

function summarizeMatrix(matrix) { 
    const acc = { 
        agents: 0, 
        present: 0, 
        retard: 0, 
        absent: 0, 
        conge: 0, 
        autorisation: 0, 
    }; 
 
    Object.keys(matrix || {}).forEach((agent) => { 
        acc.agents += 1; 
        const days = matrix[agent] || {}; 
        Object.keys(days).forEach((d) => { 
            const s = days[d]?.status; 
            if (s === "present") acc.present += 1; 
            else if (s === "retard") { 
                acc.present += 1; // retard = présent 
                acc.retard += 1; 
            } else if (s === "retard_justifie") { 
                acc.present += 1; // retard justifié = présent 
                acc.retard += 1; 
            } 
            else if (s === "absent") acc.absent += 1; 
            else if (s === "conge") acc.conge += 1; 
            else if (s === "autorisation") acc.autorisation += 1; 
        }); 
    }); 
 
    return acc;
}

new Vue({
    el: "#App",

    data() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = today.getMonth() + 1;
        return {
            isLoading: false,
            sites: [],
            filters: {
                month: mm,
                year: yyyy,
                station_id: "",
            },
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
            await this.load();
        },

        stationReportUrl(row) {
            const params = new URLSearchParams();
            params.set("month", String(this.filters.month));
            params.set("year", String(this.filters.year));
            if (row?.station_id) params.set("station_id", String(row.station_id));
            return `/reports/monthly/view?${params.toString()}`;
        },

        async load() {
            if (this.isLoading) return;
            this.isLoading = true;
            try {
                const stationId =
                    (this.$refs.stationSelect && String(this.$refs.stationSelect.value || "")) ||
                    String(this.filters.station_id || "");
                this.filters.station_id = stationId;

                destroyDatatable(this.$refs.table);

                const params = new URLSearchParams();
                params.set("month", String(this.filters.month));
                params.set("year", String(this.filters.year));
                if (stationId) params.set("station_id", stationId);

                const { data } = await get(`/rh/timesheet/monthly?${params.toString()}`);
                const stations = data?.stations ?? [];

                this.rows = stations.map((s) => {
                    const sum = summarizeMatrix(s.data || {});
                    return {
                        station_id: s.station?.id,
                        station: s.station?.name ?? "Station",
                        ...sum,
                    };
                });

                this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0));
            } catch (e) {
                this.rows = [];
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        monthOptions() {
            return [
                { value: 1, label: "Janvier" },
                { value: 2, label: "Fevrier" },
                { value: 3, label: "Mars" },
                { value: 4, label: "Avril" },
                { value: 5, label: "Mai" },
                { value: 6, label: "Juin" },
                { value: 7, label: "Juillet" },
                { value: 8, label: "Aout" },
                { value: 9, label: "Septembre" },
                { value: 10, label: "Octobre" },
                { value: 11, label: "Novembre" },
                { value: 12, label: "Decembre" },
            ];
        },

        yearOptions() {
            const current = new Date().getFullYear();
            const years = [];
            years.push(current - 1);
            years.push(current);
            for (let y = current - 2; y >= current - 10; y -= 1) {
                years.push(y);
            }
            return years;
        },

        exportPdfUrl() {
            const params = new URLSearchParams();
            params.set("month", String(this.filters.month));
            params.set("year", String(this.filters.year));
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/rh/timesheet/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            params.set("month", String(this.filters.month));
            params.set("year", String(this.filters.year));
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/rh/timesheet/export/excel?${params.toString()}`;
        },
    },
});
