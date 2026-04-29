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
        order: [[2, "desc"]], // Sort by date
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
                type: "retard",
                type_select: "retard",
                type_autre: "",
                minutes: "",
                reason: "",
                status: "pending",
            },
        };
    },

    watch: {
        'form.type_select'(val) {
            if (val !== 'autre') {
                this.form.type = val;
            } else {
                this.form.type = this.form.type_autre;
            }
        },
        'form.type_autre'(val) {
            if (this.form.type_select === 'autre') {
                this.form.type = val;
            }
        }
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.init();
    },

    methods: {
        async init() {
            try {
                // Utilisation de reference pour avoir TOUS les agents sans pagination
                const { data } = await get("/rh/conges/reference");
                this.agents = data?.agents ?? [];

                this.$nextTick(() => {
                    this.initSelect2();
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
            } catch(e) {
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
                const { data } = await postJson("/rh/authorizations/delete", { id: a.id });
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
