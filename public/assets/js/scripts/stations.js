import { get, postJson } from "../modules/http.js";

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
            filters: {
                date: `${yyyy}-${mm}-${dd}`,
            },
            form: {
                id: "",
                name: "",
                code: "",
                adresse: "",
                latlng: "",
                phone: "",
                presence: "",
            },
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
            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
                const params = new URLSearchParams();
                if (this.filters.date) params.set("date", this.filters.date);
                const { data } = await get(`/stations/list?${params.toString()}`);
                this.sites = data?.sites ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.sites = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(site) {
            this.form = {
                id: site.id,
                name: site.name ?? "",
                code: site.code ?? "",
                adresse: site.adresse ?? "",
                latlng: site.latlng ?? "",
                phone: site.phone ?? "",
                presence: site.presence ?? "",
            };
            window.$("#add_station").modal("show");
        },

        reset() {
            this.form = {
                id: "",
                name: "",
                code: "",
                adresse: "",
                latlng: "",
                phone: "",
                presence: "",
            };
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/stations/store", this.form);
                if (data?.errors) return;
                window.$("#add_station").modal("hide");
                this.reset();
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        async remove(site) {
            const ok = confirm(`Supprimer la station "${site.name}" ?`);
            if (!ok) return;

            this.isLoading = true;
            try {
                const { data } = await postJson("/table/delete", {
                    table: "sites",
                    id: site.id,
                });
                if (data?.errors) return;
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },
    },
});
