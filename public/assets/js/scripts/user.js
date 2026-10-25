import { get, post, postJson } from "../modules/http.js";
new Vue({
    el: "#App",
    data() {
        return {
            error: null,
            result: null,
            isLoading: false,
            isEditingUser: false,
            search: "",
            users: [],
            roles: [],
            prefixes: [], // Pour stocker les préfixes de sous-traitance
            actions: [],
            sites: Array.isArray(window.__SITES__) ? window.__SITES__ : [],
            regions: Array.isArray(window.__REGIONS__) ? window.__REGIONS__ : [],
            userToDelete: null,
            form: {
                name: "",
                email: "",
                password: "",
                role: "",
                station_ids: [],
                region_id: "",
                city_id: "",
                matricule_prefix: "",
                user_id: "",
                permissions: [],
            },
            formRole: {
                name: "",
                permissions: [],
                role_id: "",
            },
        };
    },

    mounted() {
        // Une fois que Vue.js est chargé, on cache le loader
        if ($("#loader").length) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.getActions();
        this.viewAllRoles();
        this.viewAllUsers();
        this.$nextTick(() => this.initStationSelect2());
        $("#add_users").on("shown.bs.modal.stationSelect", () => {
            this.$nextTick(() => this.initStationSelect2());
        });
    },

    methods: {
        viewAllUsers() {
            if (location.pathname === "/admin/users") {
                this.isDataLoading = true;
                get("/users/all")
                    .then(({ data, status }) => {
                        this.isDataLoading = false;
                        this.users = data.users;
                    })
                    .catch((err) => {
                        this.isDataLoading = false;
                    });
            }
        },

        viewAllRoles() {
            if (location.pathname === "/admin/roles" || location.pathname === "/admin/users") {
                this.isDataLoading = true;
            }
            get("/roles/all")
                .then(({ data, status }) => {
                    this.isDataLoading = false;
                    this.roles = data.roles;
                    this.prefixes = data.prefixes ?? []; // Récupération des préfixes
                })
                .catch((err) => {
                    this.isDataLoading = false;
                });
        },

        getActions() {
            get("/actions")
                .then(({ data, status }) => {
                    this.isDataLoading = false;
                    this.actions = data;
                })
                .catch((err) => {
                    this.isDataLoading = false;
                });
        },

        createUser() {
            this.isLoading = true;
            const selectedStationIds = this.$refs.stationSelect
                ? Array.from(this.$refs.stationSelect.selectedOptions).map((option) => option.value)
                : this.form.station_ids.map(String);
            const payload = {
                ...this.form,
                station_ids: selectedStationIds.includes("__all_stations__")
                    ? []
                    : selectedStationIds.map(Number),
            };
            postJson("/user/create", payload)
                .then(({ data, status }) => {
                    this.isLoading = false;
                    // Gestion des erreurs
                    if (data.errors !== undefined) {
                        this.error = data.errors;
                        alert(Array.isArray(data.errors) ? data.errors.join("\n") : data.errors);
                    }

                    if (data.message !== undefined) {
                        this.error = null;
                        this.viewAllUsers();
                        $("#add_users").modal("hide");
                        this.reset();
                    }
                })
                .catch((err) => {
                    this.isLoading = false;
                    console.log(err);
                });
        },

        createRole() {
            this.isLoading = true;
            postJson("/role/create", this.formRole)
                .then(({ data, status }) => {
                    this.isLoading = false;

                    if (data.errors) {
                        this.error = data.errors;
                        alert(Array.isArray(data.errors) ? data.errors.join("\n") : data.errors);
                        return;
                    }

                    if (data.message) {
                        this.error = null;
                        this.formRole = { name: "", permissions: [] }; // Reset form
                        this.viewAllRoles(); // Recharge la liste des rôles
                        $("#role-create").modal("hide");
                    }
                })
                .catch((err) => {
                    this.isLoading = false;
                    console.error(err);
                });
        },

        addAccess() {
            this.isLoading = true;
            postJson("/user/access", this.form)
                .then(({ data, status }) => {
                    this.isLoading = false;

                    if (data.errors) {
                        this.error = data.errors;
                        alert(Array.isArray(data.errors) ? data.errors.join("\n") : data.errors);
                        return;
                    }

                    if (data.message) {
                        this.error = null;
                        this.reset(); // Reset form
                        this.viewAllUsers(); // Recharge la liste des rôles
                        $("#access_users").modal("hide");
                    }
                })
                .catch((err) => {
                    this.isLoading = false;
                    console.error(err);
                });
        },

        editRole(role) {
            if (role.name !== "admin" || role.name !=="manager") {
                this.formRole.name = role.name;
                // role.permissions est un tableau d'objets, on récupère juste le name
                this.formRole.permissions = role.permissions.map((p) => p.name);
                this.formRole.role_id = role.id;
                $("#role-create").modal("show");
            }
        },

        editUser(user) {
            this.isEditingUser = true;
            this.form.name = user.name;
            this.form.email = user.email;
            this.form.role = user.role;
            this.form.region_id = user.region_id ?? "";
            this.form.city_id = user.city_id ?? "";
            this.form.matricule_prefix = user.matricule_prefix ?? "";
            this.form.user_id = user.id;
            this.$nextTick(() => {
                this.form.station_ids = user.stations?.length
                    ? user.stations.map((station) => station.id)
                    : user.station_id
                      ? [user.station_id]
                      : ["__all_stations__"];
                this.isEditingUser = false;
                $("#add_users").modal("show");
            });
        },

        prepareDelete(user) {
            this.userToDelete = user;
            $("#delete_modal").modal("show");
        },

        confirmDelete() {
            if (!this.userToDelete) return;
            this.isLoading = true;
            postJson("/user/delete", { user_id: this.userToDelete.id })
                .then(({ data }) => {
                    this.isLoading = false;
                    if (data.status === "error") {
                        alert(data.errors.join("\n"));
                    } else {
                        $("#delete_modal").modal("hide");
                        this.viewAllUsers();
                    }
                })
                .catch(err => {
                    this.isLoading = false;
                    alert("Une erreur est survenue lors de la suppression.");
                });
        },

        getAccess(user) {
            if (user.role !== "admin" || user.role !=='manager') {
                this.form.user_id = user.id;
                const permissions =
                    user.permissions.length > 0
                        ? user.permissions
                        : user.roles[0].permissions;
                this.form.permissions = permissions.map((p) => p.name);
                $("#access_users").modal("show");
            }
        },

        reset() {
            this.form = {
                name: "",
                email: "",
                password: "",
                role: "",
                station_ids: [],
                region_id: "",
                city_id: "",
                matricule_prefix: "",
                user_id: "",
                permissions: [],
            };

            this.formRole = {
                name: "",
                permissions: [],
                role_id: "",
            };
        },

        handleStationSelection(event) {
            const selectedIds = Array.from(event.target.selectedOptions).map((option) => option.value);
            this.form.station_ids = selectedIds.includes("__all_stations__")
                ? ["__all_stations__"]
                : selectedIds.map((id) => Number(id));
        },

        initStationSelect2() {
            const $ = window.$;
            const element = this.$refs.stationSelect;
            if (!element || !$ || !$.fn || !$.fn.select2) return;

            const select = $(element);
            if (select.hasClass("select2-hidden-accessible")) {
                select.select2("destroy");
            }

            select.select2({
                width: "100%",
                placeholder: "Sélectionner une ou plusieurs stations",
                allowClear: true,
                closeOnSelect: false,
                dropdownParent: $("#add_users"),
                templateResult: (station) => {
                    if (!station.id) return station.text;

                    const option = $(
                        '<span class="d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input mt-0" tabindex="-1" aria-hidden="true" style="pointer-events: none;"><span></span></span>'
                    );
                    option.find("input").prop("checked", station.selected);
                    option.find("span").last().text(station.text);
                    return option;
                },
            });

            this.syncStationSelect2();
        },

        syncStationSelect2() {
            const $ = window.$;
            const element = this.$refs.stationSelect;
            if (!element || !$ || !$.fn || !$.fn.select2) return;

            const select = $(element);
            if (!select.hasClass("select2-hidden-accessible")) return;

            const selectedIds = (this.form.station_ids || []).map(String);
            select.val(selectedIds).trigger("change.select2");
            $("#add_users .select2-container--open .select2-results__option").each(function () {
                const isSelected = $(this).attr("aria-selected") === "true";
                $(this).find('input[type="checkbox"]').prop("checked", isSelected);
            });
        },
    },

    watch: {
        "form.role"(value) {
            if (value === "admin") {
                this.form.station_ids = [];
                this.form.region_id = "";
                this.form.city_id = "";
                this.form.matricule_prefix = "";
            }
            if (!this.isTraitanceRole) {
                this.form.matricule_prefix = "";
            }
        },
        "form.region_id"() {
            if (this.isEditingUser) return;
            this.form.city_id = "";
            this.form.station_ids = [];
        },
        "form.city_id"() {
            if (this.isEditingUser) return;
            this.form.station_ids = [];
        },
        "form.station_ids"() {
            this.$nextTick(() => this.syncStationSelect2());
        },
        availableSites() {
            this.$nextTick(() => this.initStationSelect2());
        },
    },

    computed: {
        isTraitanceRole() {
            if (!this.form.role) return false;
            return this.form.role.toLowerCase().includes("traitance");
        },

        allActions() {
            return this.actions;
        },

        allRoles() {
            return this.roles;
        },

        allPrefixes() {
            return this.prefixes;
        },

        allUsers() {
            return this.users;
        },

        allSites() {
            return this.sites;
        },

        allRegions() {
            return this.regions;
        },

        availableCities() {
            const region = this.regions.find((item) => String(item.id) === String(this.form.region_id));
            return region ? region.city_records : [];
        },

        availableSites() {
            return this.sites.filter((site) => {
                const regionMatches = !this.form.region_id || String(site.region_id) === String(this.form.region_id);
                const cityMatches = !this.form.city_id || String(site.city_id) === String(this.form.city_id);
                return regionMatches && cityMatches;
            });
        },
    },
});
