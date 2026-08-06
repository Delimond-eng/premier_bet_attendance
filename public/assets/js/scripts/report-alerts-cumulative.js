import { get } from "../modules/http.js";

new Vue({
    el: "#App",
    data() {
        return {
            isLoading: false,
            activeTab: "absences",
            sites: [],
            filters: {
                period: "daily",
                from: moment().format("YYYY-MM-DD"),
                to: moment().format("YYYY-MM-DD"),
                station_id: "",
                threshold: 1,
            },
            range: {
                from: moment().format("DD/MM/YYYY"),
                to: moment().format("DD/MM/YYYY")
            },
            counts: {
                absences: 0,
                retards: 0,
                departs: 0,
            },
            absencesRows: [],
            retardsRows: [],
            departsRows: [],
        };
    },

    mounted() {
        const qType = new URLSearchParams(window.location.search).get("type");
        if (qType) this.activeTab = qType;

        const qFrom = new URLSearchParams(window.location.search).get("from");
        const qTo = new URLSearchParams(window.location.search).get("to");
        const qPeriod = new URLSearchParams(window.location.search).get("period");
        const qThreshold = new URLSearchParams(window.location.search).get("threshold");

        if (qFrom) this.filters.from = qFrom;
        if (qTo) this.filters.to = qTo;
        if (qPeriod) this.filters.period = qPeriod;
        if (qThreshold) this.filters.threshold = parseInt(qThreshold);

        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.init();
    },

    methods: {
        async init() {
            const { data } = await get("/stations/list");
            this.sites = data?.sites ?? [];
            await this.load();
        },

        switchTab(tab) {
            this.activeTab = tab;
        },

        async load() {
            if (this.isLoading) return;
            this.isLoading = true;

            const params = new URLSearchParams(this.filters);
            try {
                // Correction du chemin pour correspondre à la route définie dans web.php
                const { data } = await get(`/reports/alerts/cumulative/data?${params.toString()}`);

                // Mettre à jour les labels de plage
                if (data.from) this.range.from = moment(data.from).format("DD/MM/YYYY");
                if (data.to) this.range.to = moment(data.to).format("DD/MM/YYYY");

                this.absencesRows = (data?.absences ?? []).filter(r => r.agent !== null);
                this.retardsRows = (data?.retards ?? []).filter(r => r.agent !== null);
                this.departsRows = (data?.departs ?? []).filter(r => r.agent !== null);
                this.counts = data?.counts ?? { absences: 0, retards: 0, departs: 0 };
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoading = false;
            }
        },

        formatDate(d) {
            return moment(d).format("DD/MM/YYYY");
        }
    },

    computed: {
        exportPdfUrl() {
            const params = new URLSearchParams(this.filters);
            return `/reports/alerts/cumulative/export/pdf?${params.toString()}`;
        },
        exportExcelUrl() {
            const params = new URLSearchParams(this.filters);
            return `/reports/alerts/cumulative/export/excel?${params.toString()}`;
        }
    }
});
