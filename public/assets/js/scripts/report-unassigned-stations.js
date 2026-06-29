import { get } from "../modules/http.js";

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

new Vue({
    el: "#App",

    data() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, "0");
        const dd = String(today.getDate()).padStart(2, "0");
        const d = `${yyyy}-${mm}-${dd}`;

        return {
            isLoading: false,
            filters: {
                from: d,
                to: d,
            },
            rows: [],
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.load();
    },

    methods: {
        async load() {
            if (this.isLoading) return;
            this.isLoading = true;

            try {
                destroyDatatable(this.$refs.table);

                const params = new URLSearchParams();
                if (this.filters.from) params.set("from", this.filters.from);
                if (this.filters.to) params.set("to", this.filters.to);

                const { data } = await get(`/reports/alerts/unassigned/data?${params.toString()}`);

                this.rows = data?.presences ?? [];

                this.$nextTick(() => {
                    setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0);
                });
            } catch (e) {
                console.error(e);
                this.rows = [];
            } finally {
                this.isLoading = false;
            }
        },

        formatDate(date) {
            if (!date) return "-";
            return moment(date).format("DD/MM/YYYY");
        },

        formatTime(time) {
            if (!time) return "-";
            return moment(time, "HH:mm:ss").format("HH:mm");
        }
    }
});
