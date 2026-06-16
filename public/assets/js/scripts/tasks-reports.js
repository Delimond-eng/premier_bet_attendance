import { get } from '../modules/http.js';

function destroyDatatable(tableId) {
    const $ = window.$;
    if (!tableId || !$ || !$.fn || !$.fn.DataTable) return;
    if ($.fn.DataTable.isDataTable('#' + tableId)) {
        $('#' + tableId).DataTable().destroy();
    }
}

function initDatatable(tableId) {
    const $ = window.$;
    if (!$ || !$.fn || !$.fn.DataTable) return;
    $('#' + tableId).DataTable({
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

function initTooltips() {
    const $ = window.$;
    if (!$ || !bootstrap.Tooltip) return;
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

new Vue({
    el: "#ReportsApp",
    data() {
        return {
            tasks: [],
            stations: window.__STATIONS__ || [],
            agents: window.__AGENTS__ || [],
            isLoading: false,
            filters: {
                station_id: '',
                agent_id: '',
                status: '',
                from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().substr(0, 10),
                to: new Date().toISOString().substr(0, 10)
            }
        }
    },
    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        this.initSelect2();
        this.fetchTasks();
    },
    methods: {
        initSelect2() {
            const $ = window.$;
            if (!$ || !$.fn.select2) return;

            const self = this;

            // Station Select
            const $stationSelect = $('#station-select');
            $stationSelect.empty().append(new Option("Toutes les stations", ""));
            this.stations.forEach(s => {
                $stationSelect.append(new Option(s.name, s.id));
            });

            $stationSelect.select2({
                placeholder: "Toutes les stations",
                allowClear: true,
                width: '100%'
            }).on('change', function () {
                self.filters.station_id = $(this).val();
                self.fetchTasks();
            });

            // Agent Select
            const $agentSelect = $('#agent-select');
            $agentSelect.empty().append(new Option("Tous les agents", ""));
            this.agents.forEach(a => {
                $agentSelect.append(new Option(a.fullname, a.id));
            });

            $agentSelect.select2({
                placeholder: "Tous les agents",
                allowClear: true,
                width: '100%'
            }).on('change', function () {
                self.filters.agent_id = $(this).val();
                self.fetchTasks();
            });
        },
        async fetchTasks() {
            this.isLoading = true;
            try {
                destroyDatatable('reports-table');
                const queryParams = new URLSearchParams(this.filters).toString();
                const { data, status } = await get(`/tasks/data?${queryParams}`);
                if (status === 200) {
                    this.tasks = data;
                    this.$nextTick(() => {
                        setTimeout(() => {
                            initDatatable('reports-table');
                            initTooltips();
                        }, 50);
                    });
                }
            } catch (error) {
                console.error("Erreur chargement aperçu rapports:", error);
            } finally {
                this.isLoading = false;
            }
        },
        exportPdf() {
            const queryParams = new URLSearchParams(this.filters).toString();
            window.open(`/tasks/export/pdf?${queryParams}`, '_blank');
        },
        exportExcel() {
            const queryParams = new URLSearchParams(this.filters).toString();
            window.open(`/tasks/export/excel?${queryParams}`, '_blank');
        },
        formatDate(date) {
            if (!date) return '--';
            return new Date(date).toLocaleDateString('fr-FR');
        },
        formatStatus(status) {
            const map = {
                pending: 'En attente',
                in_progress: 'En cours',
                completed: 'Terminé',
                cancelled: 'Annulé'
            };
            return map[status] || status;
        },
        getStatusBadge(status) {
            const map = {
                pending: 'badge badge-soft-secondary',
                in_progress: 'badge badge-soft-info',
                completed: 'badge badge-soft-success',
                cancelled: 'badge badge-soft-danger'
            };
            return map[status] || 'badge badge-soft-light';
        }
    },
    watch: {
        "filters.status": function() { this.fetchTasks(); },
        "filters.from": function() { this.fetchTasks(); },
        "filters.to": function() { this.fetchTasks(); }
    }
});
