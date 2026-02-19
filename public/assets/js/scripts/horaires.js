import { get, postJson } from "../modules/http.js";
import { initSelect2ForVue } from "../modules/select2.js";

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
                mid_check: "",
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
            this.$nextTick(() => {
                initSelect2ForVue(this.$refs.stationFilterSelect, {
                    placeholder: "Toutes les stations",
                    getValue: () => this.filters.site_id,
                    setValue: (v) => {
                        this.filters.site_id = v;
                    },
                });
            });
        },

        stationName(id) {
            const s = this.sites.find((x) => String(x.id) === String(id));
            return s ? s.name : "--";
        },

        async load(force = false) {
            if (this.isLoading && !force) return;
            this.isLoading = true;
            try {
                const siteId =
                    (this.$refs.stationFilterSelect &&
                        String(this.$refs.stationFilterSelect.value || "")) ||
                    String(this.filters.site_id || "");
                this.filters.site_id = siteId;

                const params = new URLSearchParams();
                if (siteId) params.set("site_id", siteId);
                const { data } = await get(`/rh/horaires?${params.toString()}`);
                this.horaires = data?.horaires ?? [];
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
                mid_check: h.mid_check ?? "",
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
                mid_check: "",
                ended_at: "",
                tolerence_minutes: 15,
                site_id: "",
            };
        },

        async save() {
            this.isLoading = true;
            try {
                const payload = {
                    ...this.form,
                    mid_check: this.form.mid_check || null,
                };
                const { data } = await postJson("/rh/horaire/store", payload);
                if (data?.errors) return;
                window.$("#add_horaire").modal("hide");
                this.reset();
                this.isLoading = false;
                await this.load(true);
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
                this.isLoading = false;
                await this.load(true);
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        groupedHoraires() {
            const buckets = new Map();
            this.horaires.forEach((h) => {
                const key = h.site_id ?? "none";
                if (!buckets.has(key)) buckets.set(key, []);
                buckets.get(key).push(h);
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

        exportPdfUrl() {
            const params = new URLSearchParams();
            if (this.filters.site_id) params.set("station_id", String(this.filters.site_id));
            return `/rh/horaires/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            if (this.filters.site_id) params.set("station_id", String(this.filters.site_id));
            return `/rh/horaires/export/excel?${params.toString()}`;
        },
    },
});
