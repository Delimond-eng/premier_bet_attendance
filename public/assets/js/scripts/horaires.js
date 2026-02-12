import { get, postJson } from "../modules/http.js";

function initOrRefreshDatatable(tableEl) {
    const $ = window.$;
    if (!$ || !$.fn || !$.fn.DataTable) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        $(tableEl).DataTable().destroy();
    }

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
        return {
            isLoading: false,
            sites: [],
            horaires: [],
            filters: {
                site_id: "",
            },
            form: {
                id: "",
                libelle: "",
                started_at: "",
                ended_at: "",
                tolerence_minutes: 15,
                site_id: "",
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

        stationName(id) {
            const s = this.sites.find((x) => String(x.id) === String(id));
            return s ? s.name : "--";
        },

        async load() {
            this.isLoading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.site_id) params.set("site_id", this.filters.site_id);
                const { data } = await get(`/rh/horaires?${params.toString()}`);
                this.horaires = data?.horaires ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.horaires = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(h) {
            this.form = {
                id: h.id,
                libelle: h.libelle ?? "",
                started_at: h.started_at ?? "",
                ended_at: h.ended_at ?? "",
                tolerence_minutes: h.tolerence_minutes ?? 15,
                site_id: h.site_id ?? "",
            };
            window.$("#add_horaire").modal("show");
        },

        reset() {
            this.form = {
                id: "",
                libelle: "",
                started_at: "",
                ended_at: "",
                tolerence_minutes: 15,
                site_id: "",
            };
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/horaire/store", this.form);
                if (data?.errors) return;
                window.$("#add_horaire").modal("hide");
                this.reset();
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        async remove(h) {
            const ok = confirm(`Supprimer l'horaire "${h.libelle}" ?`);
            if (!ok) return;

            this.isLoading = true;
            try {
                const { data } = await postJson("/table/delete", {
                    table: "presence_horaires",
                    id: h.id,
                });
                if (data?.errors) return;
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },
    },
});

