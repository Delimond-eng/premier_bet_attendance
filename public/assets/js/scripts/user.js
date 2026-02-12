import { get, post, postJson } from "../modules/http.js";
new Vue({
    el: "#App",
    data() {
        return {
            error: null,
            result: null,
            isLoading: false,
            search: "",
            users: [],
            roles: [],
            actions: [],
            form: {
                name: "",
                email: "",
                password: "",
                role: "",
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
            if (location.pathname === "/admin/roles") {
                this.isDataLoading = true;
            }
            get("/roles/all")
                .then(({ data, status }) => {
                    this.isDataLoading = false;
                    this.roles = data.roles;
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
            postJson("/user/create", this.form)
                .then(({ data, status }) => {
                    this.isLoading = false;
                    // Gestion des erreurs
                    if (data.errors !== undefined) {
                        this.error = data.errors;
                        console.log(data.errors);
                    }

                    if (data.message !== undefined) {
                        this.error = null;
                        console.log(data.message);
                        this.viewAllUsers();
                        $("#add_users").modal("hide");
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
                        console.log(data.errors);
                        return;
                    }

                    if (data.message) {
                        this.error = null;
                        console.log(data.message);
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
                        console.log(data.errors);
                        return;
                    }

                    if (data.message) {
                        this.error = null;
                        console.log(data.message);
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
            if (role.name !== "admin") {
                this.formRole.name = role.name;
                // role.permissions est un tableau d'objets, on récupère juste le name
                this.formRole.permissions = role.permissions.map((p) => p.name);
                this.formRole.role_id = role.id;
                $("#role-create").modal("show");
            }
        },

        editUser(user) {
            this.form.name = user.name;
            this.form.email = user.email;
            this.form.role = user.role;
            this.form.user_id = user.id;
            $("#add_users").modal("show");
        },

        getAccess(user) {
            if (user.role !== "admin") {
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
                user_id: "",
                permissions: [],
            };

            this.formRole = {
                name: "",
                permissions: [],
                role_id: "",
            };
        },
    },

    computed: {
        allActions() {
            return this.actions;
        },

        allRoles() {
            return this.roles;
        },

        allUsers() {
            return this.users;
        },
    },
});
