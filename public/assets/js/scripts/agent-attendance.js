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

function initOrRefreshDatatable(tableEl, order = [[0, "desc"]]) {
    const $ = window.$;
    if (!$ || !$.fn || !$.fn.DataTable) return;

    destroyDatatable(tableEl);

    $(tableEl).DataTable({
        bFilter: true,
        ordering: true,
        order,
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

function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

function parseDateTime(value) {
    if (!value) return null;

    if (window.moment) {
        const m = window.moment(value, ["YYYY-MM-DD HH:mm:ss", "YYYY-MM-DDTHH:mm:ss", window.moment.ISO_8601], true);
        if (m.isValid()) return m;
        const m2 = window.moment(value);
        if (m2.isValid()) return m2;
    }

    try {
        const iso = String(value).replace(" ", "T");
        const d = new Date(iso);
        if (!Number.isNaN(d.getTime())) return d;
    } catch (_) {}

    return null;
}

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            agentId: null,
            activeTab: "presences",
            agent: {},
            schedule: null,
            todayStatus: null,
            sites: Array.isArray(window.__SITES__) ? window.__SITES__ : [],
            rows: [],
            maintenanceRows: [],
            filters: {
                from: "",
                to: "",
                status: "",
                station_id: "",
            },
            stats: {
                totalHoursPeriod: "0.0",
                presences: 0,
                retards: 0,
            },
            exportScope: "filtered",
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        this.agentId = getQueryParam("agent_id");
        if (!this.agentId) return;

        this.$nextTick(() => {
            initSelect2ForVue(this.$refs.stationSelect, {
                placeholder: "Toutes les stations",
                getValue: () => this.filters.station_id,
                setValue: (v) => {
                    this.filters.station_id = v;
                },
            });
        });

        this.initRangePicker();
        this.loadSummary();
        this.load();
        this.loadMaintenance();
    },

    methods: {
        switchTab(tab) {
            this.activeTab = tab;
            this.$nextTick(() => {
                if (tab === "presences") {
                    initOrRefreshDatatable(this.$refs.tablePresences, [[0, "desc"]]);
                } else {
                    initOrRefreshDatatable(this.$refs.tableMaintenances, [[0, "desc"]]);
                }
            });
        },

        initRangePicker() {
            const input = window.$?.(".bookingrange");
            if (!input || !input.length || !window.$?.fn?.daterangepicker || !window.moment) {
                return;
            }

            const end = window.moment();
            const start = window.moment().startOf ? window.moment().startOf("month") : window.moment();

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
                    this.loadMaintenance();
                }
            );
        },

        async loadSummary() {
            if (!this.agentId) return;

            try {
                const params = new URLSearchParams();
                params.set("agent_id", this.agentId);

                const { data } = await get(`/agents/attendances/summary?${params.toString()}`);
                this.agent = data?.agent ?? {};
                this.schedule = data?.schedule ?? null;
                this.todayStatus = data?.today_status ?? null;

                const stats = data?.stats ?? {};
                this.stats = {
                    totalHoursPeriod: String(stats.total_hours_daily ?? "0.0"),
                    presences: Number(stats.presences_monthly ?? 0),
                    retards: Number(stats.retards_monthly ?? 0),
                };
            } catch (_) {
                this.schedule = null;
                this.todayStatus = null;
                this.stats = { totalHoursPeriod: "0.0", presences: 0, retards: 0 };
            }
        },

        async refreshAll() {
            this.filters.status = "";
            this.filters.from = "";
            this.filters.to = "";
            this.filters.station_id = "";

            try {
                const input = window.$?.(".bookingrange");
                if (input && input.length) {
                    input.val("");
                }
            } catch (_) {}

            try {
                const $ = window.$;
                if (this.$refs.stationSelect && $ && $.fn && $.fn.select2) {
                    $(this.$refs.stationSelect).val("").trigger("change.select2");
                }
            } catch (_) {}

            await this.loadSummary();
            await this.load();
            await this.loadMaintenance();
        },

        openExport(format = "excel") {
            if (!this.agentId) return;
            const normalizedFormat = format === "pdf" ? "pdf" : "excel";
            const normalizedDataset = this.activeTab === "maintenances" ? "maintenances" : "presences";
            const normalizedScope = this.exportScope === "global" ? "global" : "filtered";

            const params = new URLSearchParams();
            params.set("agent_id", String(this.agentId));
            params.set("dataset", normalizedDataset);
            params.set("scope", normalizedScope);

            if (normalizedScope === "filtered") {
                if (this.filters.from) params.set("from", this.filters.from);
                if (this.filters.to) params.set("to", this.filters.to);
                if (this.filters.station_id) params.set("station_id", String(this.filters.station_id));
                if (normalizedDataset === "presences" && this.filters.status) {
                    params.set("status", this.filters.status);
                }
            }

            const url = `/agents/attendances/export/${normalizedFormat}?${params.toString()}`;
            window.open(url, "_blank");
        },

        async load() {
            if (!this.agentId) return;
            if (this.isLoading) return;

            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.tablePresences);
                const params = new URLSearchParams();
                params.set("agent_id", this.agentId);
                params.set("per_page", "500");
                if (this.filters.from) params.set("from", this.filters.from);
                if (this.filters.to) params.set("to", this.filters.to);
                if (this.filters.station_id) params.set("station_id", this.filters.station_id);

                const { data } = await get(`/agents/attendances/history?${params.toString()}`);
                const page = data?.history ?? null;
                this.rows = page?.data ?? [];
                if ((!this.agent || !this.agent.id) && this.rows.length > 0) {
                    this.agent = this.rows[0].agent ?? {};
                }

                this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.tablePresences, [[0, "desc"]]), 0));
            } catch (_) {
                this.rows = [];
                if (!this.agent) this.agent = {};
            } finally {
                this.isLoading = false;
            }
        },

        async loadMaintenance() {
            if (!this.agentId) return;

            try {
                destroyDatatable(this.$refs.tableMaintenances);

                const params = new URLSearchParams();
                params.set("agent_id", this.agentId);
                params.set("per_page", "500");
                if (this.filters.from) params.set("from", this.filters.from);
                if (this.filters.to) params.set("to", this.filters.to);
                if (this.filters.station_id) params.set("station_id", this.filters.station_id);

                const { data } = await get(`/agents/attendances/maintenance-history?${params.toString()}`);
                this.maintenanceRows = data?.maintenances?.data ?? [];

                this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.tableMaintenances, [[0, "desc"]]), 0));
            } catch (_) {
                this.maintenanceRows = [];
            }
        },

        extractMinutes(row) {
            if (row.started_at && row.ended_at) {
                const start = parseDateTime(row.started_at);
                const end = parseDateTime(row.ended_at);
                if (start && end) {
                    if (window.moment && typeof start.diff === "function") {
                        const diff = Math.max(end.diff(start, "minutes"), 0);
                        if (!Number.isNaN(diff) && diff > 0) return diff;
                    }
                    if (start instanceof Date && end instanceof Date) {
                        const diff = Math.max(Math.floor((end.getTime() - start.getTime()) / 60000), 0);
                        if (!Number.isNaN(diff) && diff > 0) return diff;
                    }
                }
            }

            const txt = String(row.duree || "");
            const h = txt.match(/(\d+)\s*h/);
            const m = txt.match(/(\d+)\s*min/);
            const hours = h ? parseInt(h[1], 10) : 0;
            const mins = m ? parseInt(m[1], 10) : 0;
            return hours * 60 + mins;
        },
    },

    computed: {
        timeSlots() {
            return [
                "06:00",
                "07:00",
                "08:00",
                "09:00",
                "10:00",
                "11:00",
                "12:00",
                "01:00",
                "02:00",
                "03:00",
                "04:00",
                "05:00",
                "06:00",
                "07:00",
                "08:00",
                "09:00",
                "10:00",
                "11:00",
            ];
        },

        highlightedTimeIndices() {
            const start = this.schedule?.expected_start || null;
            const mid = this.schedule?.expected_mid_check || null;
            const end = this.schedule?.expected_end || null;
            if (!start && !mid && !end) return { startIdx: -1, midIdx: -1, endIdx: -1 };

            const startMatches = [];
            const midMatches = [];
            const endMatches = [];
            for (let i = 0; i < this.timeSlots.length; i++) {
                if (start && this.timeSlots[i] === start) startMatches.push(i);
                if (mid && this.timeSlots[i] === mid) midMatches.push(i);
                if (end && this.timeSlots[i] === end) endMatches.push(i);
            }

            const startIdx = startMatches.length ? startMatches[0] : -1;
            let endIdx = -1;

            if (endMatches.length) {
                if (startIdx >= 0) {
                    const after = endMatches.find((i) => i > startIdx);
                    endIdx = typeof after === "number" ? after : endMatches[endMatches.length - 1];
                } else {
                    endIdx = endMatches[endMatches.length - 1];
                }
            }

            let midIdx = -1;
            if (midMatches.length) {
                if (startIdx >= 0) {
                    const between = endIdx >= 0
                        ? midMatches.find((i) => i >= startIdx && i <= endIdx)
                        : null;
                    if (typeof between === "number") {
                        midIdx = between;
                    } else {
                        const afterStart = midMatches.find((i) => i >= startIdx);
                        midIdx = typeof afterStart === "number" ? afterStart : midMatches[0];
                    }
                } else {
                    midIdx = midMatches[0];
                }
            }

            return { startIdx, midIdx, endIdx };
        },

        filteredRows() {
            if (!this.filters.status) return this.rows;

            if (this.filters.status === "present") {
                return this.rows.filter((r) => !!r.started_at);
            }

            if (this.filters.status === "absent") {
                return this.rows.filter((r) => !r.started_at);
            }

            if (this.filters.status === "late") {
                return this.rows.filter((r) => r.retard === "oui");
            }

            return this.rows;
        },

        agentStatusText() {
            if (this.todayStatus === "conge") return "En conge";
            if (this.todayStatus === "present") return "Present";
            if (this.todayStatus === "absent") return "Absent";

            const today = new Date().toISOString().slice(0, 10);
            const todayRow = this.rows.find((r) => (r.date_reference_iso || r.date_reference) === today);
            if (todayRow) {
                return todayRow.started_at ? "Present" : "Absent";
            }

            const latest = this.rows[0] ?? null;
            if (latest) {
                return latest.started_at ? "Present" : "Absent";
            }

            return "Absent";
        },

        agentStatusBadgeClass() {
            if (this.todayStatus === "conge") return "badge-primary";
            if (this.todayStatus === "present") return "badge-success";
            if (this.todayStatus === "absent") return "badge-danger";
            return this.agentStatusText === "Present" ? "badge-success" : "badge-danger";
        },

        arrivedAtText() {
            const today = new Date().toISOString().slice(0, 10);
            const todayRow = this.rows.find((r) => (r.date_reference_iso || r.date_reference) === today && r.started_at);
            const started = (todayRow?.started_at ?? this.rows.find((r) => r.started_at)?.started_at) ?? null;
            if (!started) return "--:--";

            if (typeof started === "string" && /^\d{2}:\d{2}/.test(started)) {
                return started.slice(0, 5);
            }

            if (window.moment) {
                const m = parseDateTime(started);
                if (m && typeof m.format === "function") {
                    return m.format("HH:mm");
                }
                return window.moment(started).format("HH:mm");
            }

            try {
                const parsed = parseDateTime(started);
                const d = parsed instanceof Date ? parsed : new Date(String(started).replace(" ", "T"));
                return `${String(d.getHours()).padStart(2, "0")}:${String(d.getMinutes()).padStart(2, "0")}`;
            } catch (_) {
                return "--:--";
            }
        },

        profileProgress() {
            const total = this.rows.length || 1;
            const present = this.rows.filter((r) => !!r.started_at).length;
            return Math.min(Math.max(Math.round((present / total) * 100), 0), 100);
        },

        exportScopeLabel() {
            return this.exportScope === "global" ? "Globale" : "Filtres actifs";
        },

        exportDatasetLabel() {
            return this.activeTab === "maintenances" ? "Maintenances" : "Presences";
        },
    },

    watch: {
        "filters.station_id"() {
            this.load();
            this.loadMaintenance();
        },
        "filters.status"() {
            if (this.activeTab !== "presences") return;
            destroyDatatable(this.$refs.tablePresences);
            this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.tablePresences, [[0, "desc"]]), 0));
        },
    },
});
