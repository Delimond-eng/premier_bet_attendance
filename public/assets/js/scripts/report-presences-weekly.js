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
        pageLength: 10,
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

function groupByStation(rows, statsByStationId = {}) {
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
                stats: { presences: 0, retards: 0, absents: 0 },
                rows: [],
            });
        }
        map.get(key).rows.push(r);
    }

    const grouped = Array.from(map.values());

    for (const g of grouped) {
        if (g.station_id && statsByStationId[g.station_id]) {
            g.stats = { ...g.stats, ...statsByStationId[g.station_id] };
        } else {
            const presences = g.rows.filter((x) => !!x.started_at).length;
            const retards = g.rows.filter((x) => x.retard === "oui").length;
            g.stats.presences = presences;
            g.stats.retards = retards;
        }
    }

    return grouped.sort((a, b) =>
        a.station_name.localeCompare(b.station_name, "fr", { sensitivity: "base" })
    );
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
            filters: { date: `${yyyy}-${mm}-${dd}` },
            range: { from: "", to: "" },
            rows: [],
            grouped: [],
            stationStatsById: {},
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
                const params = new URLSearchParams();
                if (this.filters.date) params.set("date", this.filters.date);
                params.set("per_page", "200");

                const { data } = await get(`/reports/weekly/data?${params.toString()}`);

                this.range = { from: data?.from ?? "", to: data?.to ?? "" };
                const stats = data?.station_stats ?? [];
                const map = {};
                for (const s of stats) {
                    map[String(s.station_id)] = {
                        presences: Number(s.presences ?? 0),
                        retards: Number(s.retards ?? 0),
                        absents: Number(s.absents ?? 0),
                    };
                }
                this.stationStatsById = map;

                this.rows = data?.presences?.data ?? [];
                this.grouped = groupByStation(this.rows, this.stationStatsById);

                this.$nextTick(() => {
                    const tables = this.$refs.tables;
                    if (Array.isArray(tables)) {
                        tables.forEach((t) => initOrRefreshDatatable(t));
                    } else if (tables) {
                        initOrRefreshDatatable(tables);
                    }
                });
            } catch (e) {
                this.range = { from: "", to: "" };
                this.rows = [];
                this.grouped = [];
                this.stationStatsById = {};
            } finally {
                this.isLoading = false;
            }
        },
    },
});

