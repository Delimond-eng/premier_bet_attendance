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

function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

new Vue({
    el: "#App",

    data() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, "0");
        const dd = String(today.getDate()).padStart(2, "0");
        const d = `${yyyy}-${mm}-${dd}`;
        const qType = getQueryParam("type");
        const qPeriod = getQueryParam("period") || "daily";
        // Si c'est journalier par defaut, on met le seuil a 1
        const qThreshold = getQueryParam("threshold") || (qPeriod === "daily" ? 1 : 3);

        const activeTab = qType === "retards"
            ? "retards"
            : (qType === "departs" ? "departs" : "absences");

        return {
            isLoading: false,
            activeTab,
            sites: [],
            filters: {
                period: qPeriod,
                from: d,
                to: d,
                threshold: parseInt(qThreshold),
                station_id: "",
            },
            range: { from: "", to: "", label: "" },
            absencesRows: [],
            retardsRows: [],
            departsRows: [],
            counts: {
                absences: 0,
                retards: 0,
                departs: 0,
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

        switchTab(tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            this.$nextTick(() => setTimeout(() => this.refreshActiveTable(), 0));
        },

        refreshActiveTable() {
            if (this.activeTab === "retards") {
                destroyDatatable(this.$refs.tableAbsences);
                destroyDatatable(this.$refs.tableDeparts);
                initOrRefreshDatatable(this.$refs.tableRetards);
                return;
            }

            if (this.activeTab === "departs") {
                destroyDatatable(this.$refs.tableAbsences);
                destroyDatatable(this.$refs.tableRetards);
                initOrRefreshDatatable(this.$refs.tableDeparts);
                return;
            }

            destroyDatatable(this.$refs.tableRetards);
            destroyDatatable(this.$refs.tableDeparts);
            initOrRefreshDatatable(this.$refs.tableAbsences);
        },

        async load() {
            if (this.isLoading) return;

            const stationId =
                (this.$refs.stationSelect && String(this.$refs.stationSelect.value || "")) ||
                String(this.filters.station_id || "");
            this.filters.station_id = stationId;

            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.tableAbsences);
                destroyDatatable(this.$refs.tableRetards);
                destroyDatatable(this.$refs.tableDeparts);

                const params = new URLSearchParams();
                params.set("period", String(this.filters.period || "daily"));
                if (this.filters.from) params.set("from", this.filters.from);
                if (this.filters.to) params.set("to", this.filters.to);
                if (stationId) params.set("station_id", stationId);
                if (this.activeTab !== "departs") {
                    params.set("threshold", String(this.filters.threshold));
                }

                const { data } = await get(`/reports/alerts/cumulative/data?${params.toString()}`);

                this.range = {
                    from: data?.from ?? "",
                    to: data?.to ?? "",
                    label: data?.period_label ?? "",
                };
                this.absencesRows = data?.absences ?? [];
                this.retardsRows = data?.retards ?? [];
                this.departsRows = data?.departs ?? [];
                this.counts = {
                    absences: data?.counts?.absences ?? this.absencesRows.length,
                    retards: data?.counts?.retards ?? this.retardsRows.length,
                    departs: data?.counts?.departs ?? this.departsRows.length,
                };

                this.$nextTick(() => setTimeout(() => this.refreshActiveTable(), 0));
            } catch (e) {
                this.range = { from: "", to: "", label: "" };
                this.absencesRows = [];
                this.retardsRows = [];
                this.departsRows = [];
                this.counts = { absences: 0, retards: 0, departs: 0 };
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        exportPdfUrl() {
            const params = new URLSearchParams();
            params.set("type", this.activeTab);
            params.set("period", String(this.filters.period || "daily"));
            if (this.filters.from) params.set("from", this.filters.from);
            if (this.filters.to) params.set("to", this.filters.to);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            if (this.activeTab !== "departs") {
                params.set("threshold", String(this.filters.threshold));
            }
            return `/reports/alerts/cumulative/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            params.set("type", this.activeTab);
            params.set("period", String(this.filters.period || "daily"));
            if (this.filters.from) params.set("from", this.filters.from);
            if (this.filters.to) params.set("to", this.filters.to);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            if (this.activeTab !== "departs") {
                params.set("threshold", String(this.filters.threshold));
            }
            return `/reports/alerts/cumulative/export/excel?${params.toString()}`;
        },
    },
});
