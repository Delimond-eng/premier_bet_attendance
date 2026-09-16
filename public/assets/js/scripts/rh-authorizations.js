import {get, postJson } from "../modules/http.js";

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
        order: [
            [2, "desc"]
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

function translateStatus(status) {
    if (status === "pending") return "En attente";
    if (status === "approved") return "Approuvé";
    if (status === "rejected") return "Rejeté";
    return status || "--";
}

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            agents: [],
            sites: Array.isArray(window.__SITES__) ? window.__SITES__ : [],
            regions: Array.isArray(window.__REGIONS__) ? window.__REGIONS__ : [],
            station_id: "",
            region_id: "",
            city_id: "",
            type_filter: "",
            from_date: "",
            to_date: "",
            authorizations: [],
            form: {
                id: "",
                agent_id: "",
                date_reference: "",
                type: "retard",
                type_select: "retard",
                type_autre: "",
                minutes: "",
                reason: "",
                status: "pending",
            },
            globalForm: {
                region_id: "",
                city_id: "",
                date_reference: new Date().toISOString().slice(0, 10),
                type: "retard",
                minutes: "",
                reason: "",
            },
        };
    },

    watch: {
        'form.type_select' (val) {
            if (val !== 'autre') {
                this.form.type = val;
            } else {
                this.form.type = this.form.type_autre;
            }
        },
        'form.type_autre' (val) {
            if (this.form.type_select === 'autre') {
                this.form.type = val;
            }
        }
    },

    computed: {
        filteredSites() {
            return this.sites.filter((site) => {
                if (this.region_id && String(site.region_id) !== String(this.region_id)) return false;
                if (this.city_id && String(site.city_id) !== String(this.city_id)) return false;
                return true;
            });
        },
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.$nextTick(() => {
            this.initRegionFilterSelect2();
            this.initCityFilterSelect2();
            this.initGlobalRegionSelect2();
            this.initGlobalCitySelect2();
        });
        this.init();
    },

    methods: {
        citiesForRegion(regionId) {
            const region = this.regions.find((item) => String(item.id) === String(regionId));
            return region && Array.isArray(region.city_records) ? region.city_records : [];
        },

        initRegionFilterSelect2() {
            this.bindSelect2(this.$refs.regionFilterSelect, "Toutes les régions", (value) => {
                this.region_id = value;
                this.city_id = "";
                this.station_id = "";
                this.$nextTick(() => {
                    this.initCityFilterSelect2();
                    this.initStationSelect2();
                });
                this.load();
            });
        },

        initCityFilterSelect2() {
            this.bindSelect2(this.$refs.cityFilterSelect, "Toutes les cités", (value) => {
                this.city_id = value;
                this.station_id = "";
                this.$nextTick(() => this.initStationSelect2());
                this.load();
            });
        },

        initGlobalRegionSelect2() {
            this.bindSelect2(this.$refs.globalRegionSelect, "Sélectionner une région", (value) => {
                this.globalForm.region_id = value;
                this.globalForm.city_id = "";
                this.$nextTick(() => this.initGlobalCitySelect2());
            }, "#global_auth_modal");
        },

        initGlobalCitySelect2() {
            this.bindSelect2(this.$refs.globalCitySelect, "Toutes les cités", (value) => {
                this.globalForm.city_id = value;
            }, "#global_auth_modal");
        },

        bindSelect2(element, placeholder, onChange, dropdownParent = null) {
            const $ = window.$;
            if (!element || !$ || !$.fn.select2) return;
            const el = $(element);
            if (el.hasClass("select2-hidden-accessible")) el.select2("destroy");
            el.select2({ width: "100%", placeholder, allowClear: true, ...(dropdownParent ? { dropdownParent: $(dropdownParent) } : {}) });
            el.off("change.rhAuth").on("change.rhAuth", () => onChange(el.val() || ""));
            el.val(element.value || "").trigger("change.select2");
        },

        initStationSelect2() {
            const $ = window.$;
            const self = this;
            const el = $(".select2-station");
            if (!el.length || !$.fn.select2) return;

            if (el.hasClass("select2-hidden-accessible")) {
                el.select2('destroy');
            }

            el.select2({
                width: '100%',
                placeholder: 'Toutes les stations',
                allowClear: true,
            }).on("change", function() {
                self.station_id = $(this).val() || "";
            });
        },

        async init() {
            try {
                const { data } = await get("/rh/conges/reference");
                this.agents = data?.agents ?? [];

                this.$nextTick(() => {
                    this.initSelect2();
                    this.initStationSelect2();
                });
            } catch (e) {
                console.error("Erreur chargement agents", e);
            }
            await this.load();
        },

        initSelect2() {
            const $ = window.$;
            const self = this;
            const el = $(".select2-agent");
            if (el.length && $.fn.select2) {
                if (el.hasClass("select2-hidden-accessible")) {
                    el.select2('destroy');
                }

                el.select2({
                    dropdownParent: $("#auth_modal"),
                    width: '100%',
                    placeholder: "--Sélectionner agent--"
                }).on("change", function() {
                    self.form.agent_id = $(this).val();
                });
            }
        },

        async load() {
            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
                const params = new URLSearchParams({ per_page: "500" });
                if (this.station_id) params.set("station_id", this.station_id);
                if (this.region_id) params.set("region_id", this.region_id);
                if (this.city_id) params.set("city_id", this.city_id);
                if (this.type_filter) params.set("type", this.type_filter);
                if (this.from_date) params.set("from", this.from_date);
                if (this.to_date) params.set("to", this.to_date);
                const { data } = await get(`/rh/authorizations?${params.toString()}`);
                this.authorizations = data?.authorizations?.data ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.authorizations = [];
            } finally {
                this.isLoading = false;
            }
        },

        onFiltersChange() {
            this.load();
        },

        resetGlobalForm() {
            this.globalForm = {
                region_id: "",
                city_id: "",
                date_reference: new Date().toISOString().slice(0, 10),
                type: "retard",
                minutes: "",
                reason: "",
            };
            this.$nextTick(() => {
                this.initGlobalRegionSelect2();
                this.initGlobalCitySelect2();
            });
        },

        async saveGlobal() {
            if (!this.globalForm.region_id || !this.globalForm.date_reference) {
                alert("Veuillez sélectionner une région et une date.");
                return;
            }
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/authorizations/global", this.globalForm);
                if (data?.errors) {
                    alert(data.errors.join("\n"));
                    return;
                }
                window.$("#global_auth_modal").modal("hide");
                Swal.fire({
                    title: "Autorisation globale accordée.",
                    text: data?.message,
                    showConfirmButton: !1,
                    showCloseButton: !0,
                    icon: "success",
                    timer: 3000,
                });
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        edit(a) {
            const standardTypes = ['retard', 'absence', 'depart', 'maladie'];
            const isAutre = !standardTypes.includes(a.type);

            this.form = {
                id: a.id,
                agent_id: a.agent_id,
                date_reference: a.date_reference ?? "",
                type: a.type ?? "",
                type_select: isAutre ? "autre" : (a.type || "retard"),
                type_autre: isAutre ? a.type : "",
                minutes: a.minutes ?? "",
                reason: a.reason ?? "",
                status: a.status ?? "pending",
            };

            this.$nextTick(() => {
                const $ = window.$;
                $(".select2-agent").val(a.agent_id).trigger("change");
                $("#auth_modal").modal("show");
            });
        },

        reset() {
            this.form = {
                id: "",
                agent_id: "",
                date_reference: "",
                type: "retard",
                type_select: "retard",
                type_autre: "",
                minutes: "",
                reason: "",
                status: "pending",
            };
            const $ = window.$;
            $(".select2-agent").val("").trigger("change");
        },

        async save() {
            if (!this.form.agent_id || !this.form.date_reference) {
                alert("Veuillez remplir les champs obligatoires (Agent et Date).");
                return;
            }

            this.isLoading = true;
            try {
                if (this.form.type_select !== 'autre') {
                    this.form.type = this.form.type_select;
                } else {
                    this.form.type = this.form.type_autre;
                }

                const { data } = await postJson("/rh/authorizations/store", this.form);
                if (data?.errors) {
                    alert(data.errors.join("\n"));
                    return;
                }
                window.$("#auth_modal").modal("hide");
                this.reset();
                await this.load();
            } catch (e) {
                console.error(e);
            } finally {
                this.isLoading = false;
            }
        },

        async remove(a) {
            const ok = confirm("Supprimer cette autorisation ?");
            if (!ok) return;
            this.isLoading = true;
            try {
                await postJson("/rh/authorizations/delete", { id: a.id });
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        statusLabel(status) {
            return translateStatus(status);
        },
    },
});
