import {get } from "../modules/http.js";
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
        order: [
            [0, "desc"]
        ],
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
            an: 0,
            conge: 0,
            autorisation: 0,
            retard_justifie: 0,
            absence_justifiee: 0,
            total_cm: 0,
            total_m: 0,
            total_cc: 0,
            total_ca: 0,
            total_other_leave_types: 0,
            total_preste: 0,
            total_overtime_minutes: 0,
            total_late_minutes: 0,
        };
        Object.keys(days).forEach((d) => {
            const day = days[d] || {};
            const s = day.status;
            const presenceCount = Number(day.presence_count || (s === 'double_shift' ? 2 : 1));

            if (day.depart === "AN") {
                acc.an += 1;
                acc.absent += 1;
            } else if (s === "present") acc.present += 1;
            else if (s === "retard") {
                acc.present += 1;
                acc.retard += 1;
            } else if (s === "retard_justifie") {
                acc.present += 1;
                acc.retard += 1;
                acc.retard_justifie += 1;
            } else if (s === "double_shift") {
                acc.present += presenceCount;
                if (day.late_first_checkin) acc.retard += 1;
            } else if (s === "absent") acc.absent += 1;
            else if (s === "conge") {
                acc.conge += 1;
                const leaveCode = String(day.arrivee || "").toUpperCase();
                if (leaveCode === "CM") acc.total_cm += 1;
                else if (leaveCode === "M") acc.total_m += 1;
                else if (leaveCode === "CC") acc.total_cc += 1;
                else if (leaveCode === "CA") acc.total_ca += 1;
                else if (leaveCode) acc.total_other_leave_types += 1;
            } else if (s === "autorisation" || s === "maladie") {
                acc.autorisation += 1;
                const leaveCode = String(day.arrivee || "").toUpperCase();
                if (leaveCode === "CM") acc.total_cm += 1;
                else if (leaveCode === "M") acc.total_m += 1;
                else if (leaveCode === "CC") acc.total_cc += 1;
                else if (leaveCode === "CA") acc.total_ca += 1;
                else if (leaveCode) acc.total_other_leave_types += 1;
            } else if (s === "absence_justifiee") acc.absence_justifiee += 1;

            if (day.overtime_minutes) {
                acc.total_overtime_minutes += day.overtime_minutes;
            }
            if (day.late_minutes) {
                acc.total_late_minutes += day.late_minutes;
            }
        });

        acc.total_preste = acc.present + acc.absence_justifiee;
        acc.overtime_display = formatOvertime(acc.total_overtime_minutes);
        acc.late_display = formatOvertime(acc.total_late_minutes);
        rows.push(acc);
    });
    return rows;
}

function mapDayStatus(status, dayData = {}) {
    if (dayData.depart === "AN") {
        return { code: "AN", cellClass: "bg-danger-subtle text-danger border", bucket: "an" };
    }

    switch (status) {
        case "present":
            return { code: "1", cellClass: "badge-success", bucket: "presence" };
        case "double_shift":
            return { code: dayData.arrivee || "2", cellClass: "badge-success", bucket: "presence" };
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
            const cCode = dayData.arrivee || "C";
            let cClass = "bg-primary text-white";
            if (cCode === 'M') cClass = "bg-warning text-dark";
            else if (cCode === 'CC') cClass = "bg-info text-white";
            else if (cCode === 'CA') cClass = "bg-success text-white";
            else if (cCode === 'CM') cClass = "bg-purple text-white";
            return { code: cCode, cellClass: cClass, bucket: "conge" };
        case "autorisation":
            const aCode = dayData.arrivee || "AS";
            let aClass = "bg-dark text-white";
            if (aCode === 'M') aClass = "bg-warning text-dark";
            else if (aCode === 'CC') aClass = "bg-info text-white";
            else if (aCode === 'CA') aClass = "bg-success text-white";
            else if (aCode === 'CM') aClass = "bg-purple text-white";
            return { code: aCode, cellClass: aClass, bucket: "autorisation" };
        case "maladie":
            return { code: "M", cellClass: "bg-warning text-dark", bucket: "autorisation" };
        case "future":
            return { code: "--", cellClass: "bg-light text-muted", bucket: null };
        case "unplanned":
            return { code: "AUT", cellClass: "bg-warning-subtle text-dark", bucket: "other" };
        default:
            return { code: "AUT", cellClass: "bg-warning-subtle text-dark", bucket: "other" };
    }
}

function getStatusLabel(code, dayData = {}) {
    switch (code) {
        case "1":
            return "Présence";
        case "2":
            return "Double shift";
        case "2 C-1":
            return "Double shift avec retard";
        case "1-R":
            return "Présence avec retard";
        case "A":
            return "Absence";
        case "AN":
            return "Entrée sans sortie";
        case "OFF":
            return "Repos";
        case "C":
        case "CONGE":
            return "Congé";
        case "CA":
            return "Congé Annuel";
        case "M":
            return "Congé Maladie / Absence Maladie";
        case "CC":
            return "Congé de Circonstance";
        case "CM":
            return "Congé de Maternité";
        case "AS":
            return "Autorisation spéciale";
        case "--":
            return "À venir";
        case "AUT":
            return "Autre";
        default:
            if (dayData.type) return dayData.type;
            return "Statut";
    }
}

function buildDayPopoverHtml(dayData = {}, code = "--") {
    const label = getStatusLabel(code, dayData);
    const lines = [];
    lines.push(`<div class="attendance-popover-header">${label}</div>`);

    const addLine = (title, value) => {
        if (value === null || value === undefined || value === "" || value === "--" || value === "--:--") return;
        lines.push(`<div class="attendance-popover-row"><span class="attendance-popover-key">${title}</span><span class="attendance-popover-value">${value}</span></div>`);
    };

    addLine("Type", dayData.type || null);

    const formatDateTime = (v) => {
        if (!v) return null;
        try {
            if (window.moment) {
                const m = moment(v);
                if (m.isValid()) return m.format('DD/MM/YYYY HH:mm');
            }
        } catch (e) {
            // ignore
        }
        return v;
    };

    // Entrée: show time and date if available
    const entreeTime = dayData.arrivee || null;
    const entreeDate = dayData.date_debut ? formatDateTime(dayData.date_debut) : null;
    const entreeDisplay = entreeTime && entreeDate ? `${entreeTime} — ${entreeDate}` : (entreeTime || entreeDate);
    addLine("Entrée", entreeDisplay);

    // Sortie: show time and date if available
    const sortieTime = dayData.depart || null;
    const sortieDate = dayData.date_fin ? formatDateTime(dayData.date_fin) : null;
    const sortieDisplay = sortieTime && sortieDate ? `${sortieTime} — ${sortieDate}` : (sortieTime || sortieDate);
    addLine("Sortie", sortieDisplay);

    addLine("Horaire", dayData.horaire || null);

    // Gestion du motif d'absence
    let motif = dayData.motif || dayData.reason;
    if (!motif && code === "A") {
        motif = "Absence non justifiée";
    }
    addLine("Motif", motif || null);

    if ((dayData.late_minutes || 0) > 0) addLine("Retard", formatOvertime(dayData.late_minutes));
    if ((dayData.overtime_minutes || 0) > 0) addLine("Heures supp.", formatOvertime(dayData.overtime_minutes));

    if (!lines.length || lines.length === 1) {
        lines.push(`<div class="attendance-popover-row"><span class="attendance-popover-key">Détail</span><span class="attendance-popover-value">Aucune information disponible</span></div>`);
    }

    return lines.join("");
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
            day_details: {},
            day_titles: {},
            total_count: 0,
            total_presences: 0,
            total_absences: 0,
            total_an: 0,
            total_retards: 0,
            total_autorisations: 0,
            total_conges: 0,
            total_cm: 0,
            total_m: 0,
            total_cc: 0,
            total_ca: 0,
            total_other_leave_types: 0,
            total_off: 0,
            total_others: 0,
            total_overtime_minutes: 0,
            total_late_minutes: 0,
        };

        dayKeys.forEach((day) => {
            const dayData = days[day] || { status: "future" };
            const status = dayData.status;
            const mapped = mapDayStatus(status, dayData);

            row.day_codes[day] = mapped.code;
            row.day_classes[day] = mapped.cellClass;
            row.day_titles[day] = getStatusLabel(mapped.code, dayData);
            row.day_details[day] = buildDayPopoverHtml(dayData, mapped.code);

            if (dayData.overtime_minutes) {
                row.total_overtime_minutes += dayData.overtime_minutes;
            }

            if (dayData.late_minutes) {
                row.total_late_minutes += dayData.late_minutes;
            }

            if (!mapped.bucket) return;

            row.total_count += 1;

            if (mapped.bucket === "presence") {
                row.total_presences += (dayData.status === "double_shift") ? (Number(dayData.presence_count || 2)) : 1;
            } else if (mapped.bucket === "retard") {
                row.total_presences += 1;
                row.total_retards += 1;
            } else if (mapped.bucket === "absence") {
                row.total_absences += 1;
            } else if (mapped.bucket === "an") {
                row.total_absences += 1;
                row.total_an += 1;
            } else if (mapped.bucket === "autorisation") {
                row.total_autorisations += 1;
                const aCode = String(dayData.arrivee || "").toUpperCase();
                if (aCode === 'CM') row.total_cm += 1;
                else if (aCode === 'M') row.total_m += 1;
                else if (aCode === 'CC') row.total_cc += 1;
                else if (aCode === 'CA') row.total_ca += 1;
                else if (aCode) row.total_other_leave_types += 1;
            } else if (mapped.bucket === "conge") {
                row.total_conges += 1;
                const cCode = String(dayData.arrivee || "").toUpperCase();
                if (cCode === 'CM') row.total_cm += 1;
                else if (cCode === 'M') row.total_m += 1;
                else if (cCode === 'CC') row.total_cc += 1;
                else if (cCode === 'CA') row.total_ca += 1;
                else if (cCode) row.total_other_leave_types += 1;
            } else if (mapped.bucket === "off") {
                row.total_off += 1;
            } else {
                row.total_others += 1;
            }
        });

        row.overtime_display = formatOvertime(row.total_overtime_minutes);
        row.late_display = formatOvertime(row.total_late_minutes);
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
        const minYear = 2024;

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
            summaryTotals: {
                present: 0,
                retard: 0,
                absent: 0,
                an: 0,
                conge: 0,
                autorisation: 0,
                retard_justifie: 0,
                absence_justifiee: 0,
                total_cm: 0,
                total_m: 0,
                total_cc: 0,
                total_ca: 0,
                total_other_leave_types: 0,
                total_preste: 0,
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

        formatDayHeader(d) {
            try {
                if (window.moment) {
                    const m = moment(d);
                    if (m.isValid()) {
                        // weekday short (Mon -> Lun), and day number
                        const weekday = m.format('ddd');
                        const daynum = m.format('D');
                        return `<div class="dp-day"><div class="dp-weekday">${weekday}</div><div class="dp-daynum">${daynum}</div></div>`;
                    }
                }
            } catch (e) {
                // ignore
            }
            // fallback: truncate if too long
            if (typeof d === 'string' && d.length > 6) return d.slice(0, 6);
            return d;
        },

        switchTab(tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            this.$nextTick(() => setTimeout(() => this.refreshActiveTable(), 0));
        },

        refreshActiveTable() {
            if (this.activeTab === "details") {
                destroyDatatable(this.$refs.tableRaw);
                // Disable ordering/search on day columns (dynamic) to remove sort arrows
                const dayCount = Array.isArray(this.dynamicDayKeys) ? this.dynamicDayKeys.length : 0;
                const dayIndices = [];
                for (let i = 0; i < dayCount; i += 1) {
                    // columns: 0=Matricule,1=Nom,2=Station, then days start at index 3
                    dayIndices.push(3 + i);
                }
                const columnDefs = [];
                if (dayIndices.length) {
                    columnDefs.push({ targets: dayIndices, orderable: false, searchable: false });
                }

                initOrRefreshDatatable(this.$refs.tableDetails, {
                    order: [
                        [1, "asc"]
                    ],
                    scrollX: true,
                    columnDefs,
                });
                return;
            }

            destroyDatatable(this.$refs.tableDetails);
            initOrRefreshDatatable(this.$refs.tableRaw, {
                order: [
                    [0, "desc"]
                ],
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

                // Suppression du filtrage local par station_id car le serveur renvoie déjà les agents filtrés par activité/planning.
                // Filtrer localement par site_id masquerait les agents planifiés mais rattachés à une autre station.
                this.rows = computeSummary(this.matrix, agentsByKey);
                this.detailedRows = computeDetailedRows(this.matrix, agentsByKey, this.dynamicDayKeys);
                this.summaryTotals = this.rows.reduce((totals, row) => ({
                    present: totals.present + (row.present || 0),
                    retard: totals.retard + (row.retard || 0),
                    absent: totals.absent + (row.absent || 0),
                    an: totals.an + (row.an || 0),
                    conge: totals.conge + (row.conge || 0),
                    autorisation: totals.autorisation + (row.autorisation || 0),
                    retard_justifie: totals.retard_justifie + (row.retard_justifie || 0),
                    absence_justifiee: totals.absence_justifiee + (row.absence_justifiee || 0),
                    total_cm: totals.total_cm + (row.total_cm || 0),
                    total_m: totals.total_m + (row.total_m || 0),
                    total_cc: totals.total_cc + (row.total_cc || 0),
                    total_ca: totals.total_ca + (row.total_ca || 0),
                    total_other_leave_types: totals.total_other_leave_types + (row.total_other_leave_types || 0),
                    total_preste: totals.total_preste + (row.total_preste || 0),
                }), {
                    present: 0,
                    retard: 0,
                    absent: 0,
                    an: 0,
                    conge: 0,
                    autorisation: 0,
                    retard_justifie: 0,
                    absence_justifiee: 0,
                    total_cm: 0,
                    total_m: 0,
                    total_cc: 0,
                    total_ca: 0,
                    total_other_leave_types: 0,
                    total_preste: 0,
                });

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
            const min = 2024;
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
