import { get, postJson } from "../modules/http.js";
import { vuePageMixin } from "../modules/vue-page-mixin.js";
import { exportMixin } from "../modules/export-mixin.js";

new Vue({
    el: "#App",
    mixins: [vuePageMixin, exportMixin],

    data() {
        return {
            error: null,
            message: null,
            isLoading: false,
            search: "",
            users: [],
            roles: [],
            actions: [],
            roleLabels: {},
            roleDescriptions: {},
            protectedRoles: ["super_admin"],
            permissionColumns: ["view", "create", "update", "validate", "delete", "export", "process"],
            columnLabels: {
                view: "Voir",
                create: "Créer",
                update: "Modifier",
                validate: "Valider",
                delete: "Supprimer",
                export: "Exporter",
                process: "Traiter",
            },
            logs: [],
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

    computed: {
        exportBase() {
            if (location.pathname.includes("/admin/users")) {
                return "/accounting/export/admin/users";
            }
            if (location.pathname.includes("/admin/roles")) {
                return "/accounting/export/admin/roles";
            }
            if (location.pathname.includes("/admin/logs")) {
                return "/accounting/export/admin/audit-logs";
            }
            return "";
        },
    },

    async mounted() {
        await this.bootPage(async () => {
            await this.loadMeta();
            if (location.pathname.includes("/admin/users")) {
                await Promise.all([this.viewAllUsers(), this.viewAllRoles()]);
            }
            if (location.pathname.includes("/admin/roles")) {
                await this.viewAllRoles();
            }
            if (location.pathname.includes("/admin/logs")) {
                await this.viewAuditLogs();
            }
        });
    },

    methods: {
        queryParams() {
            const p = new URLSearchParams();
            if (this.search) {
                p.set("search", this.search);
            }
            return p.toString();
        },

        async loadMeta() {
            const { data } = await get("/actions");
            this.actions = data.modules || data || [];
            this.roleLabels = data.role_labels || {};
            this.roleDescriptions = data.role_descriptions || {};
            this.protectedRoles = data.protected_roles || ["super_admin"];
            if (data.permission_columns) {
                this.permissionColumns = data.permission_columns;
            }
        },

        roleLabel(name) {
            return this.roleLabels[name] || name;
        },

        isProtectedRole(name) {
            return this.protectedRoles.includes(name);
        },

        moduleHasAction(module, col) {
            return (module.actions || []).some((a) => a.action === col);
        },

        formatDateTime(dt) {
            if (!dt) return "—";
            const s = String(dt).trim();
            if (/^\d{2}\/\d{2}\/\d{4}/.test(s)) {
                return s.length > 16 ? s.slice(0, 16) : s;
            }
            const d = new Date(s.includes("T") ? s : s.replace(" ", "T"));
            if (Number.isNaN(d.getTime())) return s;
            const pad = (n) => String(n).padStart(2, "0");
            return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },

        normalizeErrors(errors) {
            if (!errors) return [];
            if (Array.isArray(errors)) return errors;
            if (typeof errors === "object") {
                return Object.values(errors).flat();
            }
            return [String(errors)];
        },

        handleResponse(data) {
            if (data.errors) {
                this.error = this.normalizeErrors(data.errors);
                this.message = null;
                return false;
            }
            if (data.message) {
                this.message = data.message;
                this.error = null;
            }
            return true;
        },

        async viewAllUsers() {
            const { data, status } = await get("/users/all");
            if (status >= 400) {
                this.error = ["Impossible de charger les utilisateurs."];
                return;
            }
            this.users = data.users || [];
            if (data.role_labels) {
                this.roleLabels = data.role_labels;
            }
        },

        async viewAllRoles() {
            const { data, status } = await get("/roles/all");
            if (status >= 400) {
                this.error = ["Impossible de charger les rôles."];
                return;
            }
            this.roles = data.roles || [];
            if (data.role_labels) {
                this.roleLabels = data.role_labels;
            }
            if (data.protected_roles) {
                this.protectedRoles = data.protected_roles;
            }
        },

        async viewAuditLogs() {
            const { data, status } = await get("/audit/logs");
            if (status >= 400) {
                this.error = ["Impossible de charger le journal d'audit."];
                return;
            }
            this.logs = data.logs || [];
        },

        openCreateUser() {
            this.reset();
            this.error = null;
            this.message = null;
            $("#add_users").modal("show");
        },

        async createUser() {
            if (!this.form.role) {
                this.error = ["Veuillez sélectionner un rôle."];
                return;
            }
            if (!this.form.user_id && (!this.form.password || this.form.password.length < 6)) {
                this.error = ["Le mot de passe doit contenir au moins 6 caractères."];
                return;
            }

            this.isLoading = true;
            this.error = null;

            const payload = {
                name: this.form.name,
                email: this.form.email,
                password: this.form.password || null,
                role: this.form.role,
                user_id: this.form.user_id || null,
            };

            try {
                const { data } = await postJson("/user/create", payload);
                this.isLoading = false;
                if (!this.handleResponse(data)) {
                    return;
                }
                await this.viewAllUsers();
                $("#add_users").modal("hide");
                this.reset();
            } catch (e) {
                this.isLoading = false;
                this.error = [e.message || "Erreur lors de l'enregistrement."];
            }
        },

        async createRole() {
            this.isLoading = true;
            this.error = null;
            try {
                const { data } = await postJson("/role/create", this.formRole);
                this.isLoading = false;
                if (!this.handleResponse(data)) {
                    return;
                }
                this.formRole = { name: "", permissions: [], role_id: "" };
                await this.viewAllRoles();
                $("#role-create").modal("hide");
            } catch (e) {
                this.isLoading = false;
                this.error = [e.message || "Erreur lors de l'enregistrement du rôle."];
            }
        },

        async addAccess() {
            this.isLoading = true;
            this.error = null;
            try {
                const { data } = await postJson("/user/access", {
                    user_id: this.form.user_id,
                    permissions: this.form.permissions,
                });
                this.isLoading = false;
                if (!this.handleResponse(data)) {
                    return;
                }
                await this.viewAllUsers();
                $("#access_users").modal("hide");
            } catch (e) {
                this.isLoading = false;
                this.error = [e.message || "Erreur lors de la mise à jour des accès."];
            }
        },

        editRole(role) {
            if (this.isProtectedRole(role.name)) {
                return;
            }
            this.error = null;
            this.formRole.name = role.name;
            this.formRole.permissions = (role.permissions || []).map((p) =>
                typeof p === "string" ? p : p.name
            );
            this.formRole.role_id = role.id;
            $("#role-create").modal("show");
        },

        editUser(user) {
            this.error = null;
            this.message = null;
            const roleName = user.roles?.[0]?.name || user.role;
            this.form.name = user.name;
            this.form.email = user.email;
            this.form.role = roleName;
            this.form.user_id = user.id;
            this.form.password = "";
            $("#add_users").modal("show");
        },

        getAccess(user) {
            const roleName = user.roles?.[0]?.name || user.role;
            if (this.isProtectedRole(roleName)) {
                return;
            }
            this.error = null;
            this.form.user_id = user.id;
            const perms =
                user.permissions?.length > 0
                    ? user.permissions
                    : user.roles?.[0]?.permissions || [];
            this.form.permissions = perms.map((p) => (typeof p === "string" ? p : p.name));
            $("#access_users").modal("show");
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
        filteredLogs() {
            const q = (this.search || "").toLowerCase().trim();
            if (!q) {
                return this.logs;
            }
            return this.logs.filter(
                (l) =>
                    (l.reference || "").toLowerCase().includes(q) ||
                    (l.description || "").toLowerCase().includes(q) ||
                    (l.action || "").toLowerCase().includes(q) ||
                    (l.user_name || "").toLowerCase().includes(q)
            );
        },
        errorList() {
            return this.normalizeErrors(this.error);
        },
    },
});
