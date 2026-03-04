import { get } from "../modules/http.js";

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            range: {
                from: null,
                to: null,
                mode: "today",
            },
            maintenances: {
                summary: {
                    total: 0,
                    completed: 0,
                    ongoing: 0,
                    on_station: 0,
                    off_station: 0,
                },
                latest: [],
                progression: {
                    granularity: "day",
                    labels: [],
                    series: [],
                },
            },
            selectedMaintenance: null,
            _modal: null,
            _progressionChart: null,
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        const today = new Date().toISOString().slice(0, 10);
        this.range.mode = "today";
        this.range.from = today;
        this.range.to = today;

        this.initRangePicker();
        this.applyMode();
    },

    methods: {
        applyMode() {
            const m = window.moment;
            const now = m ? m() : null;

            if (this.range.mode === "today") {
                const d = m ? now.format("YYYY-MM-DD") : new Date().toISOString().slice(0, 10);
                this.range.from = d;
                this.range.to = d;
            }

            if (this.range.mode === "week") {
                if (m) {
                    this.range.from = now.clone().startOf("isoWeek").format("YYYY-MM-DD");
                    this.range.to = now.clone().endOf("isoWeek").format("YYYY-MM-DD");
                }
            }

            if (this.range.mode === "month") {
                if (m) {
                    this.range.from = now.clone().startOf("month").format("YYYY-MM-DD");
                    this.range.to = now.clone().endOf("month").format("YYYY-MM-DD");
                }
            }

            if (this.range.mode === "year") {
                if (m) {
                    this.range.from = now.clone().startOf("year").format("YYYY-MM-DD");
                    this.range.to = now.clone().endOf("year").format("YYYY-MM-DD");
                }
            }

            this.refresh();
        },

        initRangePicker() {
            const input = window.$?.(".bookingrange");
            if (!input || !input.length || !window.$?.fn?.daterangepicker || !window.moment) {
                return;
            }

            const start = window.moment();
            const end = window.moment();

            this.range.from = start.format("YYYY-MM-DD");
            this.range.to = end.format("YYYY-MM-DD");

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
                    this.range.mode = "custom";
                    this.range.from = startDate.format("YYYY-MM-DD");
                    this.range.to = endDate.format("YYYY-MM-DD");
                    this.refresh();
                }
            );
        },

        async refresh() {
            this.isLoading = true;

            const params = new URLSearchParams();
            if (this.range.from) params.set("from", this.range.from);
            if (this.range.to) params.set("to", this.range.to);
            if (this.range.mode) params.set("mode", this.range.mode);

            try {
                const { data } = await get(`/dashboard/stats?${params.toString()}`);
                this.maintenances = {
                    summary: {
                        total: 0,
                        completed: 0,
                        ongoing: 0,
                        on_station: 0,
                        off_station: 0,
                    },
                    latest: [],
                    progression: {
                        granularity: "day",
                        labels: [],
                        series: [],
                    },
                    ...this.maintenances,
                    ...(data?.maintenances ?? {}),
                };
            } catch (_) {
                this.maintenances = {
                    summary: {
                        total: 0,
                        completed: 0,
                        ongoing: 0,
                        on_station: 0,
                        off_station: 0,
                    },
                    latest: [],
                    progression: {
                        granularity: "day",
                        labels: [],
                        series: [],
                    },
                };
            } finally {
                this.isLoading = false;
                this.$nextTick(() => this.renderProgressionChart());
            }
        },

        renderProgressionChart() {
            const el = document.querySelector("#maintenance-progression-chart");
            if (!el) return;

            if (this._progressionChart) {
                try {
                    this._progressionChart.destroy();
                } catch (_) {}
                this._progressionChart = null;
            }

            el.innerHTML = "";

            if (!window.ApexCharts) return;

            const progression = this.maintenances?.progression ?? {};
            const labels = Array.isArray(progression.labels) ? progression.labels : [];
            const baseSeries = Array.isArray(progression.series) ? progression.series : [];

            const chartSeries = baseSeries
                .map((item) => ({
                    name: item?.name || "Station",
                    data: Array.isArray(item?.data) ? item.data.map((v) => Number(v || 0)) : [],
                }))
                .filter((item) => item.data.length > 0 && item.data.some((v) => v > 0));

            if (!labels.length || !chartSeries.length) {
                return;
            }

            const options = {
                chart: {
                    type: "line",
                    height: 360,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                },
                series: chartSeries,
                xaxis: {
                    categories: labels,
                    labels: {
                        rotate: labels.length > 10 ? -35 : 0,
                    },
                },
                yaxis: {
                    min: 0,
                    forceNiceScale: true,
                    labels: {
                        formatter: (value) => String(Math.round(Number(value || 0))),
                    },
                },
                stroke: {
                    curve: "smooth",
                    width: 3,
                },
                markers: {
                    size: 3,
                },
                dataLabels: {
                    enabled: false,
                },
                legend: {
                    position: "bottom",
                    horizontalAlign: "left",
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                },
            };

            this._progressionChart = new window.ApexCharts(el, options);
            this._progressionChart.render();
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
    },

    computed: {
        hasProgressionData() {
            const progression = this.maintenances?.progression ?? {};
            const series = Array.isArray(progression.series) ? progression.series : [];

            return series.some((item) =>
                Array.isArray(item?.data) && item.data.some((value) => Number(value || 0) > 0)
            );
        },

        progressionLabel() {
            const granularity = this.maintenances?.progression?.granularity ?? "day";
            if (granularity === "month") return "Vue mensuelle";
            if (granularity === "week") return "Vue hebdomadaire";
            return "Vue journaliere";
        },
    },

    beforeDestroy() {
        if (this._progressionChart) {
            try {
                this._progressionChart.destroy();
            } catch (_) {}
            this._progressionChart = null;
        }
    },
});
