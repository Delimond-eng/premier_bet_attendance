import {get } from "../modules/http.js";
import { initSelect2ForVue } from "../modules/select2.js";

function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

function destroyDatatable(tableEl) {
    const $ = window.$;
    if (!tableEl || !$ || !$.fn || !$.fn.DataTable) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        const dt = $(tableEl).DataTable();
        // Keep the table element in DOM (Vue owns rendering).
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
        order: [
            [4, "desc"]
        ],
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

        const qDate = getQueryParam("date");
        const qStation = getQueryParam("station_id");
        const regions = Array.isArray(window.attendanceRegions) ? window.attendanceRegions : [];

        return {
            isLoading: false,
            sites: [],
            regions,
            presences: [],
            filters: {
                date: qDate || `${yyyy}-${mm}-${dd}`,
                station_id: qStation || "",
                region_id: getQueryParam("region_id") || "",
                city_id: getQueryParam("city_id") || "",
            },
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        this.$nextTick(() => {
            this.initRegionSelect2();
            this.initCitySelect2();
        });
        this.init();
    },

    methods: {
        async init() {
            await this.loadSites();
            await this.load();
        },

        async changeRegion() {
            this.filters.station_id = "";
            this.filters.city_id = "";
            await this.refreshRegionCitySelects();
            await this.loadSites();
            await this.load();
        },

        async changeCity() {
            this.filters.station_id = "";
            await this.loadSites();
            await this.load();
        },

        citiesForRegion(regionId) {
            const region = this.regions.find((item) => String(item.id) === String(regionId));
            return region && Array.isArray(region.city_records) ? region.city_records : [];
        },

        initRegionSelect2() {
            initSelect2ForVue(this.$refs.regionSelect, {
                placeholder: "Toutes les régions",
                getValue: () => this.filters.region_id,
                setValue: (value) => {
                    if (String(this.filters.region_id || "") === String(value || "")) return;
                    this.filters.region_id = value;
                    this.changeRegion();
                },
            });
        },

        initCitySelect2() {
            initSelect2ForVue(this.$refs.citySelect, {
                placeholder: "Toutes les cités",
                getValue: () => this.filters.city_id,
                setValue: (value) => {
                    if (String(this.filters.city_id || "") === String(value || "")) return;
                    this.filters.city_id = value;
                    this.changeCity();
                },
            });
        },

        async refreshRegionCitySelects() {
            this.$nextTick(() => this.initCitySelect2());
        },

        async loadSites() {
            const params = new URLSearchParams();
            if (this.filters.region_id) params.set("region_id", this.filters.region_id);
            if (this.filters.city_id) params.set("city_id", this.filters.city_id);
            const { data } = await get(`/stations/list?${params.toString()}`);
            this.sites = data?.sites ?? [];
            if (this.filters.station_id && !this.sites.some((site) => String(site.id) === String(this.filters.station_id))) {
                this.filters.station_id = "";
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
        },

        async load() {
            if (this.isLoading) return;
            const stationId =
                (this.$refs.stationSelect && String(this.$refs.stationSelect.value || "")) ||
                String(this.filters.station_id || "");
            this.filters.station_id = stationId;

            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);

                const params = new URLSearchParams();
                if (this.filters.date) params.set("date", this.filters.date);
                if (stationId) params.set("station_id", stationId);
                if (this.filters.region_id) params.set("region_id", this.filters.region_id);
                if (this.filters.city_id) params.set("city_id", this.filters.city_id);

                const { data } = await get(`/presences/data?${params.toString()}`);
                this.presences = data?.presences ?? [];
                this.$nextTick(() => setTimeout(() => initOrRefreshDatatable(this.$refs.table), 0));
            } catch (e) {
                this.presences = [];
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        exportPdfUrl() {
            const params = new URLSearchParams();
            if (this.filters.date) params.set("date", this.filters.date);
            if (this.filters.region_id) params.set("region_id", this.filters.region_id);
            if (this.filters.city_id) params.set("city_id", this.filters.city_id);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/presences/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
            if (this.filters.date) params.set("date", this.filters.date);
            if (this.filters.region_id) params.set("region_id", this.filters.region_id);
            if (this.filters.city_id) params.set("city_id", this.filters.city_id);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            return `/presences/export/excel?${params.toString()}`;
        },
    },
});
