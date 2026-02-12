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
        return {
            isLoading: false,
            groups: [],
            horaires: [],
            form: {
                id: "",
                libelle: "",
                horaire_id: "",
                status: "actif",
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
            await this.loadHoraires();
            await this.loadGroups();
        },

        async loadHoraires() {
            const { data } = await get("/rh/horaires");
            this.horaires = data?.horaires ?? [];
        },

        async loadGroups() {
            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
                const { data } = await get("/rh/groups");
                this.groups = data?.groups ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.groups = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(g) {
            this.form = {
                id: g.id,
                libelle: g.libelle ?? "",
                horaire_id: g.horaire_id ?? "",
                status: g.status ?? "actif",
            };
            window.$("#add_group").modal("show");
        },

        reset() {
            this.form = { id: "", libelle: "", horaire_id: "", status: "actif" };
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/group/store", this.form);
                if (data?.errors) return;
                window.$("#add_group").modal("hide");
                this.reset();
                await this.loadGroups();
            } finally {
                this.isLoading = false;
            }
        },

        async remove(g) {
            const ok = confirm(`Supprimer le groupe "${g.libelle}" ?`);
            if (!ok) return;
            this.isLoading = true;
            try {
                const { data } = await postJson("/table/delete", {
                    table: "agent_groups",
                    id: g.id,
                });
                if (data?.errors) return;
                await this.loadGroups();
            } finally {
                this.isLoading = false;
            }
        },
    },
});
