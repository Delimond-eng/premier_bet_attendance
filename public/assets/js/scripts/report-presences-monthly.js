import { get } from "../modules/http.js";

function initOrRefreshDatatable(tableEl) {
    const $ = window.$;
    if (!$ || !$.fn || !$.fn.DataTable) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        $(tableEl).DataTable().destroy();
    }

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

function computeSummary(matrix, agentsByKey = {}) {
    const rows = [];
    Object.keys(matrix || {}).forEach((agent) => {
        const days = matrix[agent] || {};
        const acc = {
            agent_key: agent,
            agent: agentsByKey[agent] || { fullname: agent, matricule: "", photo: null },
            present: 0,
            retard: 0,
            absent: 0,
            conge: 0,
            autorisation: 0,
            retard_justifie: 0,
            absence_justifiee: 0,
            total_preste: 0,
        };
        Object.keys(days).forEach((d) => {
            const s = days[d]?.status;
            if (s === "present") acc.present += 1;
            else if (s === "retard") acc.retard += 1;
            else if (s === "absent") acc.absent += 1;
            else if (s === "conge") acc.conge += 1;
            else if (s === "autorisation") acc.autorisation += 1;
            else if (s === "retard_justifie") acc.retard_justifie += 1;
            else if (s === "absence_justifiee") acc.absence_justifiee += 1;
        });

        // Total presté après justification des absences.
        acc.total_preste = acc.present + acc.absence_justifiee;
        rows.push(acc);
    });
    return rows;
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
        const mm = today.getMonth() + 1;
        const minYear = 2026;

        const qMonth = parseInt(getQueryParam("month") || "", 10);
        const qYear = parseInt(getQueryParam("year") || "", 10);
        const qStation = getQueryParam("station_id");

        return {
            isLoading: false,
            sites: [],
            filters: {
                month: Number.isFinite(qMonth) && qMonth >= 1 && qMonth <= 12 ? qMonth : mm,
                year: Number.isFinite(qYear) && qYear >= minYear ? qYear : yyyy,
                station_id: qStation || "",
            },
            matrix: {},
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
            await this.load();
        },

        async load() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams();
                params.set("month", String(this.filters.month));
                params.set("year", String(this.filters.year));
                if (this.filters.station_id)
                    params.set("station_id", this.filters.station_id);

                const { data } = await get(`/reports/monthly?${params.toString()}`);
                this.matrix = data?.data ?? {};
                const agentsByKey = data?.agents ?? {};
                this.rows = computeSummary(this.matrix, agentsByKey);
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.matrix = {};
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
                { value: 2, label: "Février" },
                { value: 3, label: "Mars" },
                { value: 4, label: "Avril" },
                { value: 5, label: "Mai" },
                { value: 6, label: "Juin" },
                { value: 7, label: "Juillet" },
                { value: 8, label: "Août" },
                { value: 9, label: "Septembre" },
                { value: 10, label: "Octobre" },
                { value: 11, label: "Novembre" },
                { value: 12, label: "Décembre" },
            ];
        },

        yearOptions() {
            const current = new Date().getFullYear();
            const min = 2026;
            const years = [];
            for (let y = current; y >= min; y -= 1) {
                years.push(y);
            }
            return years;
        },

        pdfUrl() {
            const params = new URLSearchParams();
            params.set("month", String(this.filters.month));
            params.set("year", String(this.filters.year));
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            params.set("export", "pdf");
            return `/reports/monthly?${params.toString()}`;
        },
    },
});
