@extends("layouts.app")

@section("content")
    <div class="content" id="ReportsApp" v-cloak>
        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Rapports d'Intervention</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Opérations</li>
                        <li class="breadcrumb-item active" aria-current="page">Rapports des tâches</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-bottom bg-white">
                <h5 class="card-title mb-0">Filtres d'exportation</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Période (Du)</label>
                        <input type="date" class="form-control" v-model="filters.from">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Période (Au)</label>
                        <input type="date" class="form-control" v-model="filters.to">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Station / Site</label>
                        <select class="form-control" id="station-select" v-model="filters.station_id">
                            <option value="">Toutes les stations</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Agent</label>
                        <select class="form-control" id="agent-select" v-model="filters.agent_id">
                            <option value="">Tous les agents</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Statut</label>
                        <select class="form-select" v-model="filters.status">
                            <option value="">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="in_progress">En cours</option>
                            <option value="completed">Terminé</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                    @can('tasks.export')
                    <button class="btn btn-primary d-flex align-items-center" @click="exportPdf">
                        <i class="ti ti-file-type-pdf me-2 fs-18"></i>Générer PV d'intervention
                    </button>
                    <button class="btn btn-success d-flex align-items-center" @click="exportExcel">
                        <i class="ti ti-file-type-xls me-2 fs-18"></i>Exporter Suivi Excel
                    </button>
                    @else
                    <div class="alert alert-light-warning d-flex align-items-center mb-0 py-2">
                        <i class="ti ti-info-circle me-2"></i> Vous n'avez pas les permissions pour exporter ces rapports.
                    </div>
                    @endcan
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-bottom bg-white d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Aperçu des interventions (@{{ tasks.length }})</h5>
                <span class="text-muted small" v-if="isLoading">Chargement en cours...</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="reports-table">
                        <thead class="thead-light">
                            <tr>
                                <th>Mission / Titre</th>
                                <th>Site d'intervention</th>
                                <th>Assignation</th>
                                <th>Date Début</th>
                                <th>Échéance</th>
                                <th>Preuves</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="task in tasks" :key="task.id">
                                <td>
                                    <div class="fw-bold text-dark">@{{ task.title }}</div>
                                    <div class="text-muted small text-truncate" style="max-width: 200px;" :title="task.description">@{{ task.description }}</div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-map-pin text-muted me-1"></i>
                                        @{{ task.station?.name }}
                                    </div>
                                </td>
                                <td>
                                    <span v-if="task.is_global" class="badge badge-soft-info">Global</span>
                                    <div v-else class="avatar-list-stacked">
                                        <span v-for="agent in task.agents" :key="agent.id"
                                              class="avatar avatar-xs avatar-rounded"
                                              :class="agent.photo ? '' : 'bg-info-transparent border-info text-info'"
                                              data-bs-toggle="tooltip"
                                              :title="agent.fullname">
                                            <img v-if="agent.photo" :src="agent.photo" alt="img">
                                            <span v-else>@{{ agent.fullname.charAt(0).toUpperCase() }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>@{{ formatDate(task.start_date) }}</td>
                                <td>
                                    <span :class="task.is_overdue ? 'text-danger fw-bold' : ''">
                                        @{{ formatDate(task.due_date) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-soft-primary">
                                        <i class="ti ti-camera me-1"></i> @{{ task.evidences.length }} photo(s)
                                    </span>
                                </td>
                                <td>
                                    <span :class="getStatusBadge(task.status)">@{{ formatStatus(task.status) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
<script>
    window.__STATIONS__ = @json($stations ?? []);
    window.__AGENTS__ = @json($agents ?? []);
</script>
<script type="module" src="{{ asset('assets/js/scripts/tasks-reports.js') }}"></script>
@endpush
