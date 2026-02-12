import { get } from "../modules/http.js";
import { initSelect2ForVue } from "../modules/select2.js";

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
                station_id: "",
            },
            count: {
                agents: 0,
                presences: 0,
                retards: 0,
                absents: 0,
            },
            rows: [],
            grouped: [],
            stationStatsById: {},
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
            try {
                const { data } = await get("/stations/list");
                this.sites = data?.sites ?? [];
            } catch (e) {
                this.sites = [];
            }

            this.$nextTick(() => {
                initSelect2ForVue(this.$refs.stationSelect, {
                    placeholder: "Toutes les stations",
                    getValue: () => this.filters.station_id,
                    setValue: (v) => {
                        this.filters.station_id = v;
                    },
                });
            });

            await this.load();
        },

        async load() {
            if (this.isLoading) return;
            this.isLoading = true;

            const stationId =
                (this.$refs.stationSelect && String(this.$refs.stationSelect.value || "")) ||
                String(this.filters.station_id || "");
            this.filters.station_id = stationId;

            // Destroy current DataTables before Vue updates grouped tables.
            const tables = this.$refs.tables;
            if (Array.isArray(tables)) {
                tables.forEach((t) => destroyDatatable(t));
            } else if (tables) {
                destroyDatatable(tables);
            }

            const params = new URLSearchParams();
            if (this.filters.date) params.set("date", this.filters.date);
            if (stationId) params.set("station_id", stationId);
            params.set("per_page", "200");

            try {
                const { data } = await get(`/reports/daily/data?${params.toString()}`);

                this.count = data?.count ?? this.count;
                this.rows = data?.presences?.data ?? [];

                await this.loadStationStats();
                this.grouped = this.groupByStation(this.rows, this.stationStatsById);

                this.$nextTick(() => {
                    setTimeout(() => {
                        const tables = this.$refs.tables;
                        if (Array.isArray(tables)) {
                            tables.forEach((t) => initOrRefreshDatatable(t));
                        } else if (tables) {
                            initOrRefreshDatatable(tables);
                        }
                    }, 0);
                });
            } catch (e) {
                this.rows = [];
                this.grouped = [];
            } finally {
                this.isLoading = false;
            }
        },

        async loadStationStats() {
            const date = this.filters.date;
            if (!date) {
                this.stationStatsById = {};
                return;
            }

            try {
                const { data } = await get(`/stations/list?date=${encodeURIComponent(date)}`);
                const sites = data?.sites ?? [];
                const map = {};
                for (const s of sites) {
                    const agents = Number(s?.agents_count ?? 0);
                    const presences = Number(s?.presences_count ?? 0);
                    const retards = Number(s?.late_count ?? 0);
                    map[String(s.id)] = {
                        agents,
                        presences,
                        retards,
                        absents: Math.max(agents - presences, 0),
                    };
                }
                this.stationStatsById = map;
            } catch (e) {
                this.stationStatsById = {};
            }
        },

        groupByStation(rows, statsById = {}) {
            const map = new Map();
            for (const r of rows) {
                const station =
                    r?.assigned_station ||
                    r?.station_check_in ||
                    r?.station_check_out ||
                    null;

                const stationId = station?.id ? String(station.id) : "";
                const stationName = station?.name || "Sans station";
                const key = stationId ? `station:${stationId}` : `name:${stationName}`;

                if (!map.has(key)) {
                    map.set(key, {
                        key,
                        station_id: stationId || null,
                        station_name: stationName,
                        stats: { agents: 0, presences: 0, retards: 0, absents: 0 },
                        rows: [],
                    });
                }
                map.get(key).rows.push(r);
            }

            const grouped = Array.from(map.values());

            for (const g of grouped) {
                if (g.station_id && statsById[g.station_id]) {
                    g.stats = { ...g.stats, ...statsById[g.station_id] };
                } else {
                    // fallback : on calcule ce qu'on peut depuis les lignes
                    const presences = g.rows.filter((x) => !!x.started_at).length;
                    const retards = g.rows.filter((x) => x.retard === "oui").length;
                    g.stats.presences = presences;
                    g.stats.retards = retards;
                }
            }

            return grouped.sort((a, b) =>
                a.station_name.localeCompare(b.station_name, "fr", { sensitivity: "base" })
            );
        },
    },

    computed: {
        exportPdfUrl() {
            const params = new URLSearchParams();
            if (this.filters.date) params.set("date", this.filters.date);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/reports/daily/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            if (this.filters.date) params.set("date", this.filters.date);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/reports/daily/export/excel?${params.toString()}`;
        },
    },
});
