import { get } from "../modules/http.js";
import { vuePageMixin } from "../modules/vue-page-mixin.js";

const ROUTES = window.__DASHBOARD_ROUTES__ || { named: {} };

new Vue({
    el: "#App",
    mixins: [vuePageMixin],

    data() {
        return {
            data: null,
            error: null,
            isLoading: false,
            charts: {
                treso: null,
                charges: null,
                produits: null,
                resultat: null,
            },
        };
    },

    watch: {
        pageReady(isReady) {
            if (isReady && this.data?.graphiques) {
                this.scheduleCharts();
            }
        },
    },

    async mounted() {
        await this.bootPage(() => this.loadData());
        this.scheduleCharts();
    },

    methods: {
        scheduleCharts() {
            if (!this.pageReady || !this.data?.graphiques) {
                return;
            }
            this.$nextTick(() => {
                requestAnimationFrame(() => this.renderCharts());
            });
        },

        async loadData() {
            this.isLoading = true;
            this.error = null;
            try {
                const { data } = await get("/dashboard/data");
                if (data.status !== "success") {
                    this.error = data.errors || ["Erreur de chargement du tableau de bord."];
                    return;
                }
                this.data = data.data;
                this.scheduleCharts();
            } catch {
                this.error = ["Impossible de charger le tableau de bord."];
            } finally {
                this.isLoading = false;
            }
        },

        noDataOptions() {
            return {
                text: "Aucune donnée sur la période",
                align: "center",
                verticalAlign: "middle",
                style: { color: "#6c757d", fontSize: "13px" },
            };
        },

        fmt(v) {
            if (v === null || v === undefined) {
                return "—";
            }
            const n = Number(v) || 0;
            const d = this.data?.devise || "CDF";
            return (
                new Intl.NumberFormat("fr-FR", {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                }).format(n) + ` ${d}`
            );
        },

        routeUrl(name) {
            return ROUTES.named?.[name] || "#";
        },

        journalBadgeClass(type, code) {
            const c = (code || "").toUpperCase();
            if (c === "OD") return "badge-soft-secondary";
            if (c === "BQ") return "badge-soft-primary";
            if (c === "CA") return "badge-soft-warning";
            if (c === "VT") return "badge-soft-success";
            if (c === "HA") return "badge-soft-danger";
            const t = (type || "").toLowerCase();
            const map = {
                operations_diverses: "badge-soft-secondary",
                banque: "badge-soft-primary",
                caisse: "badge-soft-warning",
                ventes: "badge-soft-success",
                achats: "badge-soft-danger",
                ouverture: "badge-soft-info",
                cloture: "badge-soft-dark",
            };
            return map[t] || "badge-soft-info";
        },

        alerteBadgeClass(niveau) {
            return (
                {
                    danger: "badge-soft-danger",
                    warning: "badge-soft-warning",
                    info: "badge-soft-info",
                    success: "badge-soft-success",
                }[niveau] || "badge-soft-secondary"
            );
        },

        exerciceStatutClass(statut) {
            return (
                {
                    ouvert: "bg-success",
                    pre_cloture: "bg-warning text-dark",
                    cloture: "bg-secondary",
                    archive: "bg-dark",
                }[statut] || "bg-light text-dark"
            );
        },

        controleIcon(ok) {
            return ok ? "ti-circle-check text-success" : "ti-alert-circle text-danger";
        },

        destroyChart(key) {
            if (this.charts[key]) {
                try {
                    this.charts[key].destroy();
                } catch (_) {}
                this.charts[key] = null;
            }
        },

        renderCharts() {
            if (!window.ApexCharts || !this.data?.graphiques) {
                return;
            }
            if (!document.querySelector("#chart-treso")) {
                return;
            }
            this.renderTreso();
            this.renderDonut("chart-charges", this.data.graphiques.charges, "charges");
            this.renderDonut("chart-produits", this.data.graphiques.produits, "produits");
            this.renderResultat();
        },

        renderTreso() {
            const el = document.querySelector("#chart-treso");
            if (!el) {
                return;
            }
            this.destroyChart("treso");
            el.innerHTML = "";
            const g = this.data.graphiques.tresorerie_mensuelle || {};
            const labels = g.labels?.length ? g.labels : ["—"];
            const banque = g.banque?.length ? g.banque : [0];
            const caisse = g.caisse?.length ? g.caisse : [0];
            const total = g.total?.length ? g.total : [0];

            this.charts.treso = new ApexCharts(el, {
                series: [
                    { name: "Banque", data: banque },
                    { name: "Caisse", data: caisse },
                    { name: "Total", data: total },
                ],
                chart: {
                    type: "area",
                    height: 280,
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    fontFamily: "inherit",
                },
                colors: ["#3F7AFD", "#03C95A", "#6F42C1"],
                stroke: { curve: "smooth", width: 2 },
                fill: { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                dataLabels: { enabled: false },
                xaxis: { categories: labels },
                yaxis: {
                    labels: {
                        formatter: (v) =>
                            new Intl.NumberFormat("fr-FR", { notation: "compact" }).format(v),
                    },
                },
                legend: { position: "top" },
                noData: this.noDataOptions(),
            });
            this.charts.treso.render();
        },

        renderDonut(id, payload, key) {
            const el = document.querySelector(`#${id}`);
            if (!el) {
                return;
            }
            this.destroyChart(key);
            el.innerHTML = "";

            let series = (payload?.series || []).map((v) => Number(v) || 0);
            let labels = payload?.labels || [];
            if (!series.length || series.every((v) => v === 0)) {
                series = [1];
                labels = ["Aucune donnée"];
            }

            this.charts[key] = new ApexCharts(el, {
                series,
                labels,
                chart: { type: "donut", height: 260, fontFamily: "inherit" },
                colors: labels[0] === "Aucune donnée" ? ["#dee2e6"] : undefined,
                legend: { position: "bottom", fontSize: "11px" },
                dataLabels: {
                    enabled: labels[0] !== "Aucune donnée",
                    formatter: (v) => `${Math.round(v)}%`,
                },
                plotOptions: {
                    pie: {
                        donut: { size: "62%" },
                    },
                },
                noData: this.noDataOptions(),
            });
            this.charts[key].render();
        },

        renderResultat() {
            const el = document.querySelector("#chart-resultat");
            if (!el) {
                return;
            }
            this.destroyChart("resultat");
            el.innerHTML = "";
            const g = this.data.graphiques.resultat_mensuel || {};
            const series = g.series?.length ? g.series : [0];
            const labels = g.labels?.length ? g.labels : ["—"];
            const colors = series.map((v) => (Number(v) >= 0 ? "#03C95A" : "#E70D0D"));

            this.charts.resultat = new ApexCharts(el, {
                series: [{ name: "Résultat", data: series }],
                chart: { type: "bar", height: 260, toolbar: { show: false }, fontFamily: "inherit" },
                colors: colors.length ? colors : ["#03C95A"],
                plotOptions: {
                    bar: {
                        distributed: colors.length > 1,
                        borderRadius: 4,
                        columnWidth: "55%",
                    },
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                xaxis: { categories: labels },
                noData: this.noDataOptions(),
            });
            this.charts.resultat.render();
        },
    },
});
