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
        order: [[4, "desc"]],
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

        return {
            isLoading: false,
            sites: [],
            presences: [],
            filters: {
                date: `${yyyy}-${mm}-${dd}`,
                station_id: "",
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
            await this.loadSites();
            await this.load();
        },

        async loadSites() {
            const { data } = await get("/stations/list");
            this.sites = data?.sites ?? [];
        },

        async load() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.date) params.set("date", this.filters.date);
                if (this.filters.station_id)
                    params.set("station_id", this.filters.station_id);

                const { data } = await get(`/presences/data?${params.toString()}`);
                this.presences = data?.presences ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.presences = [];
            } finally {
                this.isLoading = false;
            }
        },
    },
});
