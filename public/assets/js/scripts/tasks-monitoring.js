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
            sLengthMenu: "_MENU_",
            searchPlaceholder: "Rechercher station...",
            info: "Affichage _START_ - _END_ sur _TOTAL_",
            paginate: {
                next: '<i class="ti ti-chevron-right"></i>',
                previous: '<i class="ti ti-chevron-left"></i> ',
            },
        },
    });
}

new Vue({
    el: "#MonitoringApp",
    data() {
        return {
            stats: {
                total: 0,
                pending: 0,
                in_progress: 0,
                completed: 0,
                overdue: 0,
                avg_global_progress: 0
            },
            stationProgress: [],
            recentEvidences: [],
            stations: window.__STATIONS__ || [],
            selectedStations: [],
            isLoading: false
        }
    },
    computed: {
        globalCompletion() {
            // Utilise la progression moyenne réelle calculée par le serveur
            return this.stats.avg_global_progress || 0;
        }
    },
    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.initSelect2();
        this.fetchData();
        // Rafraîchissement automatique toutes les 60 secondes pour le monitoring live
        this.refreshInterval = setInterval(this.fetchData, 60000);
    },
    beforeDestroy() {
        if (this.refreshInterval) clearInterval(this.refreshInterval);
    },
    methods: {
        initSelect2() {
            const $ = window.$;
            if (!$ || !$.fn.select2) return;

            const self = this;
            const $select = $('#filter-station-monitoring');

            // On remplit les options via JS
            $select.empty();
            this.stations.forEach(s => {
                $select.append(new Option(s.name, s.id));
            });

            $select.select2({
                placeholder: " Filtrer par sites",
                allowClear: true,
                width: '100%'
            }).on('change', function () {
                self.selectedStations = $(this).val() || [];
                self.fetchData();
            });
        },
        async fetchData() {
            this.isLoading = true;
            try {
                destroyDatatable('monitoring-table');

                let url = '/tasks/monitoring/data';
                const params = new URLSearchParams();
                if (this.selectedStations && this.selectedStations.length > 0) {
                    this.selectedStations.forEach(id => params.append('station_ids[]', id));
                }

                const queryString = params.toString();
                if (queryString) {
                    url += '?' + queryString;
                }

                const { data, status } = await get(url);
                if (status === 200) {
                    this.stats = data.stats;
                    this.stationProgress = data.stationProgress;
                    this.recentEvidences = data.recentEvidences;

                    this.$nextTick(() => {
                        setTimeout(() => initDatatable('monitoring-table'), 50);
                    });
                }
            } catch (error) {
                console.error("Erreur monitoring:", error);
            } finally {
                this.isLoading = false;
            }
        },
        getProgressClass(progress) {
            if (progress >= 100) return 'bg-success';
            if (progress > 50) return 'bg-info';
            if (progress > 0) return 'bg-warning';
            return 'bg-secondary';
        },
        formatTimeAgo(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diffInMinutes = Math.floor((now - date) / 60000);

            if (diffInMinutes < 1) return "À l'instant";
            if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`;
            if (diffInMinutes < 1440) return `Il y a ${Math.floor(diffInMinutes / 60)} h`;
            return date.toLocaleDateString('fr-FR');
        }
    }
});
