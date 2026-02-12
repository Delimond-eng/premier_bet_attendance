import { get, postJson } from "../modules/http.js";

function initOrRefreshDatatable(tableEl) {
    const $ = window.$;
    if (!$ || !$.fn || !$.fn.DataTable) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        $(tableEl).DataTable().destroy();
    }

    $(tableEl).DataTable({
        bFilter: true,
        ordering: true,
        order: [[3, "desc"]],
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

function statusClass(status) {
    if (status === "approved") return "badge-soft-success";
    if (status === "rejected") return "badge-soft-danger";
    return "badge-soft-warning";
}

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            rows: [],
            agents: [],
            types: [],
            defaultAvatar: "https://smarthr.co.in/demo/html/template/assets/img/users/user-26.jpg",
            form: {
                id: "",
                agent_id: "",
                conge_type_id: "",
                date_debut: "",
                date_fin: "",
                motif: "",
                status: "pending",
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
        statusLabel(s) {
            return translateStatus(s);
        },
        statusClass(s) {
            return statusClass(s);
        },

        async init() {
            const { data } = await get("/rh/conges/reference");
            this.agents = data?.agents ?? [];
            this.types = data?.types ?? [];
            await this.load();
        },

        async load() {
            this.isLoading = true;
            try {
                const { data } = await get("/rh/attributions?per_page=500");
                this.rows = data?.attributions?.data ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.rows = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(c) {
            this.form = {
                id: c.id,
                agent_id: c.agent_id,
                conge_type_id: c.conge_type_id ?? "",
                date_debut: c.date_debut ?? "",
                date_fin: c.date_fin ?? "",
                motif: c.motif ?? "",
                status: c.status ?? "pending",
            };
            window.$("#attribution_modal").modal("show");
        },

        reset() {
            this.form = {
                id: "",
                agent_id: "",
                conge_type_id: "",
                date_debut: "",
                date_fin: "",
                motif: "",
                status: "pending",
            };
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/attributions/store", this.form);
                if (data?.errors) {
                    alert((data.errors || []).join("\n"));
                    return;
                }
                window.$("#attribution_modal").modal("hide");
                this.reset();
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        async remove(c) {
            const ok = confirm("Supprimer cette assignation ?");
            if (!ok) return;
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/attributions/delete", { id: c.id });
                if (data?.errors) return;
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },
    },
});

