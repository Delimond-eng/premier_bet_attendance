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
        return {
            isLoading: false,
            sites: [],
            groups: [],
            horaires: [],
            form: {
                id: "",
                libelle: "",
                horaire_id: "",
                status: "actif",
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
            await this.loadStations();
            await this.loadHoraires();
            await this.loadGroups();
        },

        async loadStations() {
            try {
                const { data } = await get("/stations/list");
                this.sites = data?.sites ?? [];
            } catch (e) {
                this.sites = [];
            }
        },

        async loadHoraires() {
            const { data } = await get("/rh/horaires");
            this.horaires = data?.horaires ?? [];
        },

        async loadGroups() {
            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
                const { data } = await get("/rh/groups");
                this.groups = data?.groups ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.groups = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(g) {
            this.form = {
                id: g.id,
                libelle: g.libelle ?? "",
                horaire_id: g.horaire_id ?? "",
                status: g.status ?? "actif",
            };
            window.$("#add_group").modal("show");
        },

        reset() {
            this.form = { id: "", libelle: "", horaire_id: "", status: "actif" };
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/group/store", this.form);
                if (data?.errors) return;
                window.$("#add_group").modal("hide");
                this.reset();
                await this.loadGroups();
            } finally {
                this.isLoading = false;
            }
        },

        async remove(g) {
            const ok = confirm(`Supprimer le groupe "${g.libelle}" ?`);
            if (!ok) return;
            this.isLoading = true;
            try {
                const { data } = await postJson("/table/delete", {
                    table: "agent_groups",
                    id: g.id,
                });
                if (data?.errors) return;
                await this.loadGroups();
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        groupedGroups() {
            const buckets = new Map();
            this.groups.forEach((g) => {
                const siteId = g?.horaire?.site_id ?? "none";
                if (!buckets.has(siteId)) buckets.set(siteId, []);
                buckets.get(siteId).push(g);
            });

            const groups = [];
            for (const [key, rows] of buckets.entries()) {
                let stationName = "Station non affectee";
                if (key !== "none") {
                    const s = this.sites.find((x) => String(x.id) === String(key));
                    stationName = s ? s.name : `Station ${key}`;
                }
                groups.push({
                    key,
                    station_name: stationName,
                    rows,
                });
            }

            return groups.sort((a, b) => String(a.station_name).localeCompare(String(b.station_name)));
        },
    },
});
