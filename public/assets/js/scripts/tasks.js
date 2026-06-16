import { get, postJson } from '../modules/http.js';

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
    el: "#TaskApp",
    data() {
        return {
            tasks: [],
            stations: window.__STATIONS__ || [],
            agents: window.__AGENTS__ || [],
            isLoading: false,
            isSaving: false,
            filters: {
                station_id: '',
                status: '',
                start_date: '',
                end_date: ''
            },
            form: {
                id: null,
                title: '',
                station_id: '',
                description: '',
                priority: 'medium',
                start_date: new Date().toISOString().substr(0, 10),
                due_date: new Date().toISOString().substr(0, 10),
                is_global: false,
                agent_ids: [],
                subtasks: ['']
            },
            selectedTask: {
                id: null,
                title: '',
                description: '',
                station: null,
                agents: [],
                subtasks: [],
                evidences: [],
                status: '',
                priority: '',
                start_date: '',
                due_date: '',
                is_global: false
            }
        }
    },
    computed: {
        quickStats() {
            return [
                { label: 'Tâches Totales', value: this.tasks.length, bgClass: 'bg-dark', icon: 'ti ti-list-details' },
                { label: 'En cours', value: this.tasks.filter(t => t.status === 'in_progress').length, bgClass: 'bg-info', icon: 'ti ti-loader' },
                { label: 'Terminées', value: this.tasks.filter(t => t.status === 'completed').length, bgClass: 'bg-success', icon: 'ti ti-check' },
                { label: 'En retard', value: this.tasks.filter(t => t.is_overdue).length, bgClass: 'bg-danger', icon: 'ti ti-alert-triangle' },
            ];
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

            // Filtre de la liste
            const $filterSelect = $('#filter-station-id');
            $filterSelect.empty().append(new Option("Toutes les stations", ""));
            this.stations.forEach(s => {
                $filterSelect.append(new Option(s.name, s.id));
            });

            $filterSelect.select2({
                placeholder: "Toutes les stations",
                allowClear: true,
                width: '100%'
            }).on('change', function () {
                self.filters.station_id = $(this).val();
                self.fetchTasks();
            });

            // Select station dans le formulaire
            const $formStationSelect = $('#form-station-id');
            $formStationSelect.empty().append(new Option("Choisir un site", ""));
            this.stations.forEach(s => {
                $formStationSelect.append(new Option(s.name, s.id));
            });

            $formStationSelect.select2({
                placeholder: "Choisir un site",
                dropdownParent: $('#task-offcanvas'),
                width: '100%'
            }).on('change', function () {
                const val = $(this).val();
                self.form.station_id = val;
            });

            // Select agents dans le formulaire (Multiple) - Affiche tous les agents
            const $formAgentSelect = $('#form-agent-ids');

            // Initialisation avec tous les agents
            $formAgentSelect.empty();
            this.agents.forEach(agent => {
                // On peut ajouter le nom du site pour aider à identifier l'agent
                const site = this.stations.find(s => s.id == agent.site_id);
                const label = site ? `${agent.fullname} (${site.name})` : agent.fullname;
                $formAgentSelect.append(new Option(label, agent.id));
            });

            $formAgentSelect.select2({
                placeholder: "Sélectionner les techniciens (tous les sites)",
                dropdownParent: $('#task-offcanvas'),
                width: '100%',
                multiple: true
            }).on('change', function () {
                const selectedValues = $(this).val() || [];
                self.form.agent_ids = selectedValues;
            });
        },
        async fetchTasks() {
            this.isLoading = true;
            try {
                destroyDatatable('tasks-table');
                const queryParams = new URLSearchParams(this.filters).toString();
                const { data, status } = await get(`/tasks/data?${queryParams}`);
                if (status === 200) {
                    this.tasks = data;
                    this.$nextTick(() => {
                        setTimeout(() => {
                            initDatatable('tasks-table');
                            initTooltips();
                        }, 50);
                    });
                }
            } catch (error) {
                console.error("Erreur chargement tâches:", error);
            } finally {
                this.isLoading = false;
            }
        },
        openCreateOffcanvas() {
            this.resetForm();
            $('#form-station-id').val('').trigger('change.select2');
            $('#form-agent-ids').val([]).trigger('change.select2');
            const el = document.getElementById('task-offcanvas');
            if (el) {
                const offcanvas = new bootstrap.Offcanvas(el);
                offcanvas.show();
            }
        },
        viewDetails(task) {
            this.selectedTask = JSON.parse(JSON.stringify(task));
            const el = document.getElementById('task-details-offcanvas');
            if (el) {
                const offcanvas = new bootstrap.Offcanvas(el);
                offcanvas.show();
            }
        },
        editTask(task) {
            this.form = {
                id: task.id,
                title: task.title,
                station_id: task.station_id,
                description: task.description,
                priority: task.priority,
                start_date: task.start_date ? task.start_date.split('T')[0] : '',
                due_date: task.due_date ? task.due_date.split('T')[0] : '',
                is_global: !!task.is_global,
                agent_ids: task.agents ? task.agents.map(a => a.id) : [],
                subtasks: task.subtasks && task.subtasks.length ? task.subtasks.map(s => s.title) : ['']
            };

            // Sync select2 station
            $('#form-station-id').val(task.station_id).trigger('change.select2');

            // Sync select2 agents
            this.$nextTick(() => {
                $('#form-agent-ids').val(this.form.agent_ids).trigger('change.select2');
            });

            const el = document.getElementById('task-offcanvas');
            if (el) {
                const offcanvas = new bootstrap.Offcanvas(el);
                offcanvas.show();
            }
        },
        async deleteTask(task) {
            const ok = await Swal.fire({
                title: 'Suppression',
                text: `Voulez-vous supprimer la tâche "${task.title}" ?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            });

            if (ok.isConfirmed) {
                try {
                    const { data, status } = await postJson('/table/delete', {
                        table: 'tasks',
                        id: task.id
                    });
                    if (status === 200) {
                        Swal.fire('Supprimé', 'La tâche a été supprimée', 'success');
                        this.fetchTasks();
                    }
                } catch (error) {
                    Swal.fire('Erreur', 'Impossible de supprimer la tâche', 'error');
                }
            }
        },
        resetForm() {
            this.form = {
                id: null,
                title: '',
                station_id: '',
                description: '',
                priority: 'medium',
                start_date: new Date().toISOString().substr(0, 10),
                due_date: new Date().toISOString().substr(0, 10),
                is_global: false,
                agent_ids: [],
                subtasks: ['']
            };
        },
        addSubtask() {
            this.form.subtasks.push('');
        },
        removeSubtask(index) {
            this.form.subtasks.splice(index, 1);
        },
        async saveTask() {
            this.isSaving = true;
            try {
                const { data, status } = await postJson('/tasks/store', this.form);
                if (status === 200 || status === 201) {
                    Swal.fire('Succès', data.message, 'success');
                    const el = document.getElementById('task-offcanvas');
                    if (el) {
                        const instance = bootstrap.Offcanvas.getInstance(el);
                        if (instance) instance.hide();
                    }
                    this.fetchTasks();
                } else {
                    Swal.fire('Erreur', data.error || 'Erreur lors de l\'enregistrement', 'error');
                }
            } catch (error) {
                Swal.fire('Erreur', 'Une erreur est survenue', 'error');
            } finally {
                this.isSaving = false;
            }
        },
        calculateProgress(task) {
            if (!task.subtasks || task.subtasks.length === 0) {
                return task.status === 'completed' ? 100 : 0;
            }
            const completed = task.subtasks.filter(st => st.is_completed).length;
            return Math.round((completed / task.subtasks.length) * 100);
        },
        formatDate(date) {
            if (!date) return '--';
            const d = new Date(date);
            return d.toLocaleDateString('fr-FR');
        },
        formatDateTime(date) {
            if (!date) return '--';
            const d = new Date(date);
            return d.toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
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
        },
        getPriorityBadge(priority) {
            const map = {
                low: 'badge badge-soft-secondary',
                medium: 'badge badge-soft-warning',
                high: 'badge badge-soft-danger'
            };
            return map[priority] || 'badge badge-soft-light';
        },
        zoomImage(url) {
            Swal.fire({
                imageUrl: url,
                imageAlt: 'Preuve terrain',
                showCloseButton: true,
                showConfirmButton: false,
                width: 'auto'
            });
        }
    }
});
