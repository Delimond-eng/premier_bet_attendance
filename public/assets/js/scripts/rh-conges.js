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
        order: [[0, "asc"]],
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

function translateTypeStatus(status) {
    if (status === "actif") return "Actif";
    if (status === "inactif") return "Inactif";
    return status || "--";
}

function typeStatusClass(status) {
    if (status === "actif") return "badge-soft-success";
    if (status === "inactif") return "badge-soft-danger";
    return "badge-soft-secondary";
}

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            types: [],
            form: {
                id: "",
                libelle: "",
                description: "",
                status: "actif",
            },
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.load();
    },

    methods: {
        typeStatusLabel(status) {
            return translateTypeStatus(status);
        },

        typeStatusClass(status) {
            return typeStatusClass(status);
        },

        async load() {
            this.isLoading = true;
            try {
                destroyDatatable(this.$refs.table);
                const { data } = await get("/rh/conges?per_page=500");
                this.types = data?.conges?.data ?? [];
                this.$nextTick(() => initOrRefreshDatatable(this.$refs.table));
            } catch (e) {
                this.types = [];
            } finally {
                this.isLoading = false;
            }
        },

        edit(t) {
            this.form = {
                id: t.id,
                libelle: t.libelle ?? "",
                description: t.description ?? "",
                status: t.status ?? "actif",
            };
            window.$("#conge_type_modal").modal("show");
        },

        reset() {
            this.form = {
                id: "",
                libelle: "",
                description: "",
                status: "actif",
            };
        },

        async save() {
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/conges/store", this.form);
                if (data?.errors) {
                    alert((data.errors || []).join("\n"));
                    return;
                }
                window.$("#conge_type_modal").modal("hide");
                this.reset();
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },

        async remove(t) {
            const ok = confirm(`Supprimer le type de congé "${t.libelle}" ?`);
            if (!ok) return;
            this.isLoading = true;
            try {
                const { data } = await postJson("/rh/conges/delete", { id: t.id });
                if (data?.errors) return;
                await this.load();
            } finally {
                this.isLoading = false;
            }
        },
    },
});
