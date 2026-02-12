import { get, postJson } from "../modules/http.js";

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
        order: [[1, "desc"]],
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
            authorizations: [],
            form: {
                id: "",
                agent_id: "",
                date_reference: "",
                type: "",
                minutes: "",
                reason: "",
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
        async init() {
            const { data } = await get("/agents/data?per_page=200");
            this.agents = data?.agents?.data ?? [];
            await this.load();
        },

        async load() {
            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
                const { data } = await get("/rh/authorizations?per_page=500");
                this.authorizations = data?.authorizations?.data ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.authorizations = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(a) {
            this.form = {
                id: a.id,
                agent_id: a.agent_id,
                date_reference: a.date_reference ?? "",
                type: a.type ?? "",
                minutes: a.minutes ?? "",
                reason: a.reason ?? "",
                status: a.status ?? "pending",
            };
            window.$("#auth_modal").modal("show");
        },

        reset() {
            this.form = {
                id: "",
                agent_id: "",
                date_reference: "",
                type: "",
                minutes: "",
                reason: "",
                status: "pending",
            };
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/authorizations/store", this.form);
                if (data?.errors) return;
                window.$("#auth_modal").modal("hide");
                this.reset();
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        async remove(a) {
            const ok = confirm("Supprimer cette autorisation ?");
            if (!ok) return;
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/authorizations/delete", { id: a.id });
                if (data?.errors) return;
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
