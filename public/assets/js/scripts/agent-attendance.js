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
            agent: {},
            schedule: null,
            todayStatus: null,
            sites: Array.isArray(window.__SITES__) ? window.__SITES__ : [],
            rows: [],
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
    },

    methods: {
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
        },

        async load() {
            if (!this.agentId) return;
            if (this.isLoading) return;

            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
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

                this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0));
            } catch (e) {
                this.rows = [];
                if (!this.agent) this.agent = {};
            } finally {
                this.isLoading = false;
            }
        },

        recomputeStats() {
            const presentRows = this.rows.filter((r) => !!r.started_at);
            const lateRows = this.rows.filter((r) => r.retard === "oui");

            let totalMinutes = 0;
            for (const r of presentRows) {
                const mins = this.extractMinutes(r);
                totalMinutes += mins;
            }

            this.stats = {
                totalHoursPeriod: (totalMinutes / 60).toFixed(1),
                presences: presentRows.length,
                retards: lateRows.length,
            };
        },

        extractMinutes(row) {
            // Priorité : started_at/ended_at (plus fiable). Sinon : champ duree ("8h 30min").
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
            // Keep the same visual "timeline" already used in the template.
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
            const end = this.schedule?.expected_end || null;
            if (!start && !end) return { startIdx: -1, endIdx: -1 };

            const startMatches = [];
            const endMatches = [];
            for (let i = 0; i < this.timeSlots.length; i++) {
                if (start && this.timeSlots[i] === start) startMatches.push(i);
                if (end && this.timeSlots[i] === end) endMatches.push(i);
            }

            const startIdx = startMatches.length ? startMatches[0] : -1;
            let endIdx = -1;

            if (endMatches.length) {
                // Prefer an end index after the chosen start index, to avoid highlighting the wrong duplicate.
                if (startIdx >= 0) {
                    const after = endMatches.find((i) => i > startIdx);
                    endIdx = typeof after === "number" ? after : endMatches[endMatches.length - 1];
                } else {
                    endIdx = endMatches[endMatches.length - 1];
                }
            }

            return { startIdx, endIdx };
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
            if (this.todayStatus === "conge") return "En congé";
            if (this.todayStatus === "present") return "Présent";
            if (this.todayStatus === "absent") return "Absent";

            const today = new Date().toISOString().slice(0, 10);
            const todayRow = this.rows.find((r) => (r.date_reference_iso || r.date_reference) === today);
            if (todayRow) {
                return todayRow.started_at ? "Présent" : "Absent";
            }

            const latest = this.rows[0] ?? null;
            if (latest) {
                return latest.started_at ? "Présent" : "Absent";
            }

            return "Absent";
        },

        agentStatusBadgeClass() {
            if (this.todayStatus === "conge") return "badge-primary";
            if (this.todayStatus === "present") return "badge-success";
            if (this.todayStatus === "absent") return "badge-danger";
            return this.agentStatusText === "Présent" ? "badge-success" : "badge-danger";
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
            // Simple valeur de 0-100 basée sur présence dans la période (visuel).
            const total = this.rows.length || 1;
            const present = this.rows.filter((r) => !!r.started_at).length;
            return Math.min(Math.max(Math.round((present / total) * 100), 0), 100);
        },
    },

    watch: {
        "filters.station_id"() {
            this.load();
        },
        "filters.status"() {
            destroyDatatable(this.$refs.table);
            this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0));
        },
    },
});
