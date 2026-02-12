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

function translateKind(kind) {
    if (kind === "retard") return "Retard";
    if (kind === "absence") return "Absence";
    return kind || "--";
}

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            kind: document.getElementById("App")?.dataset?.kind ?? "",
            agents: [],
            justifications: [],
            form: {
                id: "",
                agent_id: "",
                date_reference: "",
                kind: "",
                justification: "",
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
            this.form.kind = this.kind;
            const { data } = await get("/agents/data?per_page=200");
            this.agents = data?.agents?.data ?? [];
            await this.load();
        },

        async load() {
            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
                const params = new URLSearchParams();
                params.set("per_page", "500");
                if (this.kind) params.set("kind", this.kind);
                const { data } = await get(`/rh/justifications?${params.toString()}`);
                this.justifications = data?.justifications?.data ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.justifications = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(j) {
            this.form = {
                id: j.id,
                agent_id: j.agent_id,
                date_reference: j.date_reference ?? "",
                kind: j.kind ?? this.kind,
                justification: j.justification ?? "",
                status: j.status ?? "pending",
            };
            window.$("#justif_modal").modal("show");
        },

        reset() {
            this.form = {
                id: "",
                agent_id: "",
                date_reference: "",
                kind: this.kind,
                justification: "",
                status: "pending",
            };
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/justifications/store", this.form);
                if (data?.errors) return;
                window.$("#justif_modal").modal("hide");
                this.reset();
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        async remove(j) {
            const ok = confirm("Supprimer cette justification ?");
            if (!ok) return;
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/justifications/delete", { id: j.id });
                if (data?.errors) return;
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        statusLabel(status) {
            return translateStatus(status);
        },

        kindLabel(kind) {
            return translateKind(kind);
        },
    },
});
