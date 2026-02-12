import { get } from "../modules/http.js";

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            error: null,
            counts: {
                sites: 0,
                agents: 0,
                presences: 0,
                retards: 0,
                absents: 0,
            },
            authorizations: {
                maladies: 0,
                conges: 0,
                autres: 0,
            },
            charts: {
                labels: [],
                dates: [],
                series: {
                    present: [],
                    late: [],
                    absent: [],
                },
            },
            latestCheckins: [],
            _apexStatusChart: null,
            _apexLeaveChart: null,
            _chartJsTrend: null,
            range: {
                from: null,
                to: null,
                mode: "week",
            },
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        this.initRangePicker();
        this.applyMode();
        this.refresh();
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
                    this.range.from = now.clone().startOf("week").format("YYYY-MM-DD");
                    this.range.to = now.clone().endOf("week").format("YYYY-MM-DD");
                }
            }

            if (this.range.mode === "month") {
                if (m) {
                    this.range.from = now.clone().startOf("month").format("YYYY-MM-DD");
                    this.range.to = now.clone().endOf("month").format("YYYY-MM-DD");
                }
            }

            // mode custom => se base sur le daterangepicker
            this.refresh();
        },

        initRangePicker() {
            const input = window.$?.(".bookingrange");
            if (!input || !input.length || !window.$?.fn?.daterangepicker || !window.moment) {
                return;
            }

            const end = window.moment();
            const start = window.moment().subtract(6, "days");

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
            this.error = null;

            const params = new URLSearchParams();
            if (this.range.from) params.set("from", this.range.from);
            if (this.range.to) params.set("to", this.range.to);

            try {
                const { data } = await get(`/dashboard/stats?${params.toString()}`);

                if (data?.errors) {
                    this.error = data.errors;
                    this.isLoading = false;
                    return;
                }

                this.counts = { ...this.counts, ...(data?.count ?? {}) };
                this.charts = { ...this.charts, ...(data?.charts ?? {}) };
                this.authorizations = { ...this.authorizations, ...(data?.authorizations ?? {}) };
                this.latestCheckins = data?.latest_checkins ?? [];

                this.renderStatusApex();
                this.renderLeaveApex();
                this.renderTrendChartJs();
            } catch (e) {
                this.error = ["Erreur lors du chargement des statistiques."];
            } finally {
                this.isLoading = false;
            }
        },

        renderStatusApex() {
            if (!window.ApexCharts) return;

            const el = document.querySelector("#status-chart");
            if (!el) return;

            if (this._apexStatusChart) {
                try {
                    this._apexStatusChart.destroy();
                } catch (_) {}
                this._apexStatusChart = null;
            }

            el.innerHTML = "";

            const options = {
                series: [
                    { name: "Présents", data: [this.counts.presences || 0] },
                    { name: "Retards", data: [this.counts.retards || 0] },
                    { name: "Absents", data: [this.counts.absents || 0] },
                ],
                chart: {
                    type: "bar",
                    height: 60,
                    stacked: true,
                    stackType: "100%",
                    toolbar: { show: false },
                    sparkline: { enabled: true },
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        barHeight: "100%",
                    },
                },
                colors: ["#03C95A", "#FFC107", "#E70D0D"],
                dataLabels: { enabled: false },
                xaxis: { categories: ["Total"] },
                tooltip: { enabled: true },
                legend: { show: false },
            };

            this._apexStatusChart = new ApexCharts(el, options);
            this._apexStatusChart.render();
        },

        renderLeaveApex() {
            if (!window.ApexCharts) return;

            const el = document.querySelector("#leave-chart");
            if (!el) return;

            if (this._apexLeaveChart) {
                try {
                    this._apexLeaveChart.destroy();
                } catch (_) {}
                this._apexLeaveChart = null;
            }

            el.innerHTML = "";

            const options = {
                series: [
                    this.authorizations.maladies || 0,
                    this.authorizations.conges || 0,
                    this.authorizations.autres || 0,
                ],
                chart: {
                    type: "donut",
                    height: 160,
                    toolbar: { show: false },
                },
                labels: ["Malades", "Congés", "Autres"],
                colors: ["#0DCAF0", "#6F42C1", "#ADB5BD"],
                legend: { show: false },
                dataLabels: { enabled: false },
            };

            this._apexLeaveChart = new ApexCharts(el, options);
            this._apexLeaveChart.render();
        },

        renderTrendChartJs() {
            if (!window.Chart) return;

            const container = document.getElementById("attendance-chart");
            if (!container) return;

            if (this._chartJsTrend) {
                this._chartJsTrend.destroy();
                this._chartJsTrend = null;
            }

            // Le template peut déjà avoir rendu un ApexChart sur ce conteneur.
            // On remplace le contenu pour garantir la présence du canvas.
            container.innerHTML = '<canvas id="attendance-chart-js" height="180"></canvas>';

            const canvas = document.getElementById("attendance-chart-js");
            if (!canvas) return;

            const ctx = canvas.getContext("2d");
            if (!ctx) return;

            const rawLabels = this.charts?.labels ?? [];
            const dates = this.charts?.dates ?? [];
            let labels = rawLabels;

            if (this.range.mode === "week" && dates.length > 0 && dates.length <= 7 && window.moment) {
                window.moment.locale("fr");
                labels = dates.map((d) => {
                    const txt = window.moment(d).format("ddd");
                    return txt.charAt(0).toUpperCase() + txt.slice(1);
                });
            }

            const series = this.charts?.series ?? {};

            this._chartJsTrend = new Chart(ctx, {
                type: "line",
                data: {
                    labels,
                    datasets: [
                        {
                            label: "Présents",
                            data: series.present ?? [],
                            borderColor: "#03C95A",
                            backgroundColor: "rgba(3, 201, 90, 0.15)",
                            tension: 0.35,
                            fill: true,
                        },
                        {
                            label: "Retards",
                            data: series.late ?? [],
                            borderColor: "#FFC107",
                            backgroundColor: "rgba(255, 193, 7, 0.12)",
                            tension: 0.35,
                            fill: true,
                        },
                        {
                            label: "Absents",
                            data: series.absent ?? [],
                            borderColor: "#E70D0D",
                            backgroundColor: "rgba(231, 13, 13, 0.08)",
                            tension: 0.35,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: "bottom" },
                        tooltip: { enabled: true },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                        },
                    },
                },
            });
        },
    },
});

