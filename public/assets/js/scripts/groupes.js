import { get, postJson } from "../modules/http.js";

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
                station_id: "",
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
                const { data } = await get("/rh/groups");
                this.groups = data?.groups ?? [];
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
                station_id: g?.horaire?.site_id ?? "",
                horaire_id: g.horaire_id ?? "",
                status: g.status ?? "actif",
            };
            window.$("#add_group").modal("show");
        },

        reset() {
            this.form = { id: "", libelle: "", station_id: "", horaire_id: "", status: "actif" };
        },

        stationName(id) {
            const s = this.sites.find((x) => String(x.id) === String(id));
            return s ? s.name : "--";
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/group/store", this.form);
                if (data?.errors) return;
                window.$("#add_group").modal("hide");
                this.reset();
                this.isLoading = false;
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
                this.isLoading = false;
                await this.loadGroups();
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        filteredHoraires() {
            if (!this.form.station_id) {
                return this.horaires;
            }
            const stationId = String(this.form.station_id);
            return this.horaires.filter((h) => String(h.site_id) === stationId);
        },
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

    watch: {
        "form.station_id"(value) {
            if (!value || !this.form.horaire_id) return;
            const keep = this.horaires.some(
                (h) => String(h.id) === String(this.form.horaire_id) && String(h.site_id) === String(value)
            );
            if (!keep) {
                this.form.horaire_id = "";
            }
        },
    },
});
