import { get } from "../modules/http.js";
import { initSelect2ForVue } from "../modules/select2.js";

function destroyDatatable(tableEl) {
    const $ = window.$;
    if (!tableEl || !$ || !$.fn || !$.fn.DataTable) return;

    if ($.fn.DataTable.isDataTable(tableEl)) {
        const dt = $(tableEl).DataTable();
        dt.destroy();
    }
}

function initOrRefreshDatatable(tableEl, options = {}) {
    const $ = window.$;
    if (!$ || !$.fn || !$.fn.DataTable || !tableEl) return;

    destroyDatatable(tableEl);

    $(tableEl).DataTable({
        bFilter: true,
        ordering: true,
        order: [[0, "desc"]],
        info: true,
        scrollX: false,
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
        ...options,
    });
}

function formatOvertime(minutes) {
    if (!minutes || minutes <= 0) return "0h";
    const h = Math.floor(minutes / 60);
    const m = minutes % 60;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
}

function computeSummary(matrix, agentsByKey = {}) {
    const rows = [];
    Object.keys(matrix || {}).forEach((agent) => {
        const days = matrix[agent] || {};
        const acc = {
            agent_key: agent,
            agent: agentsByKey[agent] || { fullname: agent, matricule: "", photo: null },
            present: 0,
            retard: 0,
            absent: 0,
            conge: 0,
            autorisation: 0,
            retard_justifie: 0,
            absence_justifiee: 0,
            total_preste: 0,
            total_overtime_minutes: 0,
        };
        Object.keys(days).forEach((d) => {
            const day = days[d] || {};
            const s = day.status;
            if (s === "present") acc.present += 1;
            else if (s === "retard") {
                acc.present += 1;
                acc.retard += 1;
            } else if (s === "retard_justifie") {
                acc.present += 1;
                acc.retard += 1;
                acc.retard_justifie += 1;
            } else if (s === "absent") acc.absent += 1;
            else if (s === "conge") acc.conge += 1;
            else if (s === "autorisation") acc.autorisation += 1;
            else if (s === "absence_justifiee") acc.absence_justifiee += 1;

            if (day.overtime_minutes) {
                acc.total_overtime_minutes += day.overtime_minutes;
            }
        });

        acc.total_preste = acc.present + acc.absence_justifiee;
        acc.overtime_display = formatOvertime(acc.total_overtime_minutes);
        rows.push(acc);
    });
    return rows;
}

function mapDayStatus(status) {
    switch (status) {
    case "present":
        return { code: "1", cellClass: "badge-success", bucket: "presence" };
    case "retard":
    case "retard_justifie":
        return { code: "1-R", cellClass: "bg-info text-white", bucket: "retard" };
    case "absent":
        return { code: "A", cellClass: "bg-danger text-white", bucket: "absence" };
    case "absence_justifiee":
        return { code: "A", cellClass: "bg-warning text-dark", bucket: "absence" };
    case "off":
        return { code: "OFF", cellClass: "bg-secondary text-white", bucket: "off" };
    case "conge":
        return { code: "C", cellClass: "bg-primary text-white", bucket: "conge" };
    case "autorisation":
        return { code: "AS", cellClass: "bg-dark text-white", bucket: "autorisation" };
    case "future":
        return { code: "--", cellClass: "bg-light text-muted", bucket: null };
    case "unplanned":
        return { code: "AUT", cellClass: "bg-warning-subtle text-dark", bucket: "other" };
    default:
        return { code: "AUT", cellClass: "bg-warning-subtle text-dark", bucket: "other" };
    }
}

function computeDetailedRows(matrix, agentsByKey = {}, dayKeys = []) {
    const rows = [];

    Object.keys(matrix || {}).forEach((agent) => {
        const days = matrix[agent] || {};
        const row = {
            agent_key: agent,
            agent: agentsByKey[agent] || { fullname: agent, matricule: "", photo: null },
            day_codes: {},
            day_classes: {},
            total_count: 0,
            total_presences: 0,
            total_absences: 0,
            total_retards: 0,
            total_autorisations: 0,
            total_conges: 0,
            total_off: 0,
            total_others: 0,
            total_overtime_minutes: 0,
        };

        dayKeys.forEach((day) => {
            const dayData = days[day] || { status: "future" };
            const status = dayData.status;
            const mapped = mapDayStatus(status);

            row.day_codes[day] = mapped.code;
            row.day_classes[day] = mapped.cellClass;

            if (dayData.overtime_minutes) {
                row.total_overtime_minutes += dayData.overtime_minutes;
            }

            if (!mapped.bucket) return;

            row.total_count += 1;

            if (mapped.bucket === "presence") {
                row.total_presences += 1;
            } else if (mapped.bucket === "retard") {
                row.total_presences += 1;
                row.total_retards += 1;
            } else if (mapped.bucket === "absence") {
                row.total_absences += 1;
            } else if (mapped.bucket === "autorisation") {
                row.total_autorisations += 1;
            } else if (mapped.bucket === "conge") {
                row.total_conges += 1;
            } else if (mapped.bucket === "off") {
                row.total_off += 1;
            } else {
                row.total_others += 1;
            }
        });

        row.overtime_display = formatOvertime(row.total_overtime_minutes);
        rows.push(row);
    });

    return rows;
}

function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

new Vue({
    el: "#App",

    data() {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = today.getMonth() + 1;
        const minYear = 2026;

        const qMonth = parseInt(getQueryParam("month") || "", 10);
        const qYear = parseInt(getQueryParam("year") || "", 10);
        const qStation = getQueryParam("station_id");

        const qFrom = getQueryParam("from");
        const qTo = getQueryParam("to");

        return {
            isLoading: false,
            activeTab: "brut",
            sites: [],
            prefixes: [],
            show_matricule_filter: false,
            useRange: !!(qFrom && qTo),
            filters: {
                month: Number.isFinite(qMonth) && qMonth >= 1 && qMonth <= 12 ? qMonth : mm,
                year: Number.isFinite(qYear) && qYear >= minYear ? qYear : yyyy,
                station_id: qStation || "",
                matricule_prefix: "",
                from: qFrom || "",
                to: qTo || "",
            },
            matrix: {},
            rows: [],
            detailedRows: [],
            dynamicDayKeys: [],
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
            const { data } = await get("/stations/list");
            this.sites = data?.sites ?? [];
            this.$nextTick(() => {
                initSelect2ForVue(this.$refs.stationSelect, {
                    placeholder: "Toutes les stations",
                    getValue: () => this.filters.station_id,
                    setValue: (v) => {
                        this.filters.station_id = v;
                    },
                });
                this.initRangePicker();
            });
            await this.load();
        },

        initRangePicker() {
            const $ = window.$;
            if (!$ || !$.fn || !$.fn.daterangepicker) return;

            const self = this;
            const start = this.filters.from ? moment(this.filters.from) : moment().startOf('month');
            const end = this.filters.to ? moment(this.filters.to) : moment().endOf('month');

            $('#reportRange').daterangepicker({
                startDate: start,
                endDate: end,
                opens: 'left',
                locale: {
                    format: 'DD/MM/YYYY',
                    applyLabel: "Appliquer",
                    cancelLabel: "Annuler",
                    daysOfWeek: ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"],
                    monthNames: ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"],
                    firstDay: 1
                }
            }, (start, end) => {
                self.filters.from = start.format('YYYY-MM-DD');
                self.filters.to = end.format('YYYY-MM-DD');
                // Mise à jour automatique après sélection
                self.load();
            });

            // Valeur initiale de l'input
            if (this.filters.from && this.filters.to) {
                $('#reportRange').val(moment(this.filters.from).format('DD/MM/YYYY') + ' - ' + moment(this.filters.to).format('DD/MM/YYYY'));
            }
        },

        switchTab(tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            this.$nextTick(() => setTimeout(() => this.refreshActiveTable(), 0));
        },

        refreshActiveTable() {
            if (this.activeTab === "details") {
                destroyDatatable(this.$refs.tableRaw);
                initOrRefreshDatatable(this.$refs.tableDetails, {
                    order: [[1, "asc"]],
                    scrollX: true,
                });
                return;
            }

            destroyDatatable(this.$refs.tableDetails);
            initOrRefreshDatatable(this.$refs.tableRaw, {
                order: [[0, "desc"]],
            });
        },

        async load() {
            if (this.isLoading) return;
            this.isLoading = true;
            try {
                const stationId =
                    (this.$refs.stationSelect && String(this.$refs.stationSelect.value || "")) ||
                    String(this.filters.station_id || "");
                this.filters.station_id = stationId;

                destroyDatatable(this.$refs.tableRaw);
                destroyDatatable(this.$refs.tableDetails);

                const params = new URLSearchParams();
                if (this.useRange && this.filters.from && this.filters.to) {
                    params.set("from", this.filters.from);
                    params.set("to", this.filters.to);
                } else {
                    params.set("month", String(this.filters.month));
                    params.set("year", String(this.filters.year));
                }

                if (stationId) params.set("station_id", stationId);
                if (this.filters.matricule_prefix) params.set("matricule_prefix", this.filters.matricule_prefix);

                const { data } = await get(`/reports/monthly?${params.toString()}`);
                this.matrix = data?.data ?? {};
                this.prefixes = data?.prefixes ?? [];
                this.show_matricule_filter = !!data?.show_matricule_filter;
                this.dynamicDayKeys = data?.days ?? [];

                const agentsByKey = data?.agents ?? {};

                let rows = computeSummary(this.matrix, agentsByKey);
                let detailedRows = computeDetailedRows(this.matrix, agentsByKey, this.dynamicDayKeys);

                if (stationId) {
                    rows = rows.filter(
                        (r) => String(r?.agent?.station_id ?? "") === String(stationId)
                    );
                    detailedRows = detailedRows.filter(
                        (r) => String(r?.agent?.station_id ?? "") === String(stationId)
                    );
                }

                this.rows = rows;
                this.detailedRows = detailedRows;
                this.$nextTick(() => setTimeout(() => this.refreshActiveTable(), 0));
            } catch (e) {
                this.matrix = {};
                this.rows = [];
                this.detailedRows = [];
                this.dynamicDayKeys = [];
            } finally {
                this.isLoading = false;
            }
        },
    },

    computed: {
        monthOptions() {
            return [
                { value: 1, label: "Janvier" },
                { value: 2, label: "Fevrier" },
                { value: 3, label: "Mars" },
                { value: 4, label: "Avril" },
                { value: 5, label: "Mai" },
                { value: 6, label: "Juin" },
                { value: 7, label: "Juillet" },
                { value: 8, label: "Aout" },
                { value: 9, label: "Septembre" },
                { value: 10, label: "Octobre" },
                { value: 11, label: "Novembre" },
                { value: 12, label: "Decembre" },
            ];
        },

        yearOptions() {
            const current = new Date().getFullYear();
            const min = 2026;
            const years = [];
            for (let y = current; y >= min; y -= 1) {
                years.push(y);
            }
            return years;
        },

        exportPdfUrl() {
            const params = new URLSearchParams();
            if (this.useRange && this.filters.from && this.filters.to) {
                params.set("from", this.filters.from);
                params.set("to", this.filters.to);
            } else {
                params.set("month", String(this.filters.month));
                params.set("year", String(this.filters.year));
            }
            params.set("tab", this.activeTab);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            if (this.filters.matricule_prefix) params.set("matricule_prefix", this.filters.matricule_prefix);
            return `/reports/monthly/export/pdf?${params.toString()}`;
        },

        exportExcelUrl() {
            const params = new URLSearchParams();
             if (this.useRange && this.filters.from && this.filters.to) {
                params.set("from", this.filters.from);
                params.set("to", this.filters.to);
            } else {
                params.set("month", String(this.filters.month));
                params.set("year", String(this.filters.year));
            }
            params.set("tab", this.activeTab);
            if (this.filters.station_id) params.set("station_id", this.filters.station_id);
            if (this.filters.matricule_prefix) params.set("matricule_prefix", this.filters.matricule_prefix);
            return `/reports/monthly/export/excel?${params.toString()}`;
        },
    },

    watch: {
        useRange(val) {
            if (val) {
                this.$nextTick(() => this.initRangePicker());
            }
        }
    }
});
