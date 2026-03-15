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

new Vue({
    el: "#App",

    data() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, "0");
        const dd = String(today.getDate()).padStart(2, "0");

        const firstDayOfMonth = `${yyyy}-${mm}-01`;
        const currentDate = `${yyyy}-${mm}-${dd}`;

        return {
            isLoading: false,
            sites: [],
            agents: [],
            rows: [],
            selectedMaintenance: null,
            summary: {
                total: 0,
                completed: 0,
                ongoing: 0,
                on_station: 0,
                off_station: 0,
            },
            filters: {
                from: firstDayOfMonth,
                to: currentDate,
                station_id: "",
                agent_id: "",
            },
            _modal: null,
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        this.initRangePicker();
        this.init();
    },

    methods: {
        async init() {
            await this.loadStations();
            await this.loadAgents();

            this.$nextTick(() => {
                initSelect2ForVue(this.$refs.stationSelect, {
                    placeholder: "Toutes les stations",
                    getValue: () => this.filters.station_id,
                    setValue: async (v) => {
                        this.filters.station_id = v;
                        await this.loadAgents();
                    },
                });

                initSelect2ForVue(this.$refs.agentSelect, {
                    placeholder: "Tous les agents",
                    getValue: () => this.filters.agent_id,
                    setValue: (v) => {
                        this.filters.agent_id = v;
                    },
                });
            });

            await this.load();
        },

        initRangePicker() {
            const input = window.$?.(".bookingrange");
            if (!input || !input.length || !window.$?.fn?.daterangepicker || !window.moment) {
                return;
            }

            const start = window.moment(this.filters.from, "YYYY-MM-DD");
            const end = window.moment(this.filters.to, "YYYY-MM-DD");

            input.daterangepicker(
                {
                    startDate: start,
                    endDate: end,
                    locale: {
                        format: "DD/MM/YYYY",
                        applyLabel: "Appliquer",
                        cancelLabel: "Annuler",
                    },
                },
                (startDate, endDate) => {
                    this.filters.from = startDate.format("YYYY-MM-DD");
                    this.filters.to = endDate.format("YYYY-MM-DD");
                    this.load();
                }
            );
        },

        async loadStations() {
            try {
                const { data } = await get("/stations/list");
                this.sites = data?.sites ?? [];
            } catch (_) {
                this.sites = [];
            }
        },

        async loadAgents() {
            const params = new URLSearchParams();
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);

            try {
                const { data } = await get(`/reports/maintenance/agents?${params.toString()}`);
                this.agents = data?.agents ?? [];

                if (this.filters.agent_id) {
                    const keep = this.agents.some((a) => String(a.id) === String(this.filters.agent_id));
                    if (!keep) {
                        this.filters.agent_id = "";
                    }
                }

                this.$nextTick(() => {
                    const $ = window.$;
                    if (this.$refs.agentSelect && $ && $.fn && $.fn.select2) {
                        $(this.$refs.agentSelect).val(this.filters.agent_id || "").trigger("change.select2");
                    }
                });
            } catch (_) {
                this.agents = [];
            }
        },

        async load() {
            if (this.isLoading) return;
            this.isLoading = true;

            try {
                destroyDatatable(this.$refs.table);

                const stationId =
                    (this.$refs.stationSelect && String(this.$refs.stationSelect.value || "")) ||
                    String(this.filters.station_id || "");
                const agentId =
                    (this.$refs.agentSelect && String(this.$refs.agentSelect.value || "")) ||
                    String(this.filters.agent_id || "");

                this.filters.station_id = stationId;
                this.filters.agent_id = agentId;

                const params = new URLSearchParams();
                if (this.filters.from) params.set("from", this.filters.from);
                if (this.filters.to) params.set("to", this.filters.to);
                if (stationId) params.set("station_id", stationId);
                if (agentId) params.set("agent_id", agentId);
                params.set("per_page", "500");

                const { data } = await get(`/reports/maintenance/data?${params.toString()}`);
                this.summary = { ...this.summary, ...(data?.summary ?? {}) };
                this.rows = data?.maintenances?.data ?? [];

                this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0));
            } catch (_) {
                this.rows = [];
                this.summary = {
                    total: 0,
                    completed: 0,
                    ongoing: 0,
                    on_station: 0,
                    off_station: 0,
                };
            } finally {
                this.isLoading = false;
            }
        },

        openDetails(item) {
            this.selectedMaintenance = item;
            if (!window.bootstrap?.Modal) return;

            if (!this._modal) {
                const el = document.getElementById("maintenanceDetailsModal");
                if (!el) return;
                this._modal = new window.bootstrap.Modal(el);
            }
            this._modal.show();
        },

        exportReport(format = "excel") {
            const params = new URLSearchParams();
            if (this.filters.from) params.set("from", this.filters.from);
            if (this.filters.to) params.set("to", this.filters.to);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            if (this.filters.agent_id) params.set("agent_id", this.filters.agent_id);

            const baseUrl = format === "pdf" ? "/reports/maintenance/export/pdf" : "/reports/maintenance/export/excel";
            window.open(`${baseUrl}?${params.toString()}`, "_blank");
        },
    },
});
