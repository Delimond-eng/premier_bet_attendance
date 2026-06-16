@extends("layouts.app")

@section("content")
    <style>
        /* Correction de l'alignement vertical du Select2 Multiple */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #dee2e6 !important;
            min-height: 38px !important;
            padding: 0 8px !important;
            display: flex !important;
            align-items: center !important;
            border-radius: 6px !important;
        }
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .select2-container--default .select2-selection--multiple .select2-search__field {
            margin: 0 !important;
            padding: 0 !important;
            height: 36px !important;
            line-height: 36px !important;
        }
    </style>

    <div class="content" id="MonitoringApp" v-cloak>
        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Suivi de Progression (Monitoring)</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Opérations</li>
                        <li class="breadcrumb-item active" aria-current="page">Monitoring des tâches</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="me-2 mb-2">
                    <button class="btn btn-white border d-flex align-items-center" @click="fetchData">
                        <i class="ti ti-refresh me-2"></i>Actualiser
                    </button>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-lg-3 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="fs-12 fw-medium mb-1">Taux de Complétion Global</p>
                                <h4>@{{ globalCompletion }}%</h4>
                            </div>
                            <span class="avatar avatar-md bg-soft-primary rounded-circle">
                                <i class="ti ti-chart-pie text-primary"></i>
                            </span>
                        </div>
                        <div class="progress mt-3" style="height: 7px;">
                            <div class="progress-bar bg-primary" :style="{ width: globalCompletion + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="fs-12 fw-medium mb-1">Missions en Retard</p>
                                <h4>@{{ stats.overdue }}</h4>
                            </div>
                            <span class="avatar avatar-md bg-soft-danger rounded-circle">
                                <i class="ti ti-alert-triangle text-danger"></i>
                            </span>
                        </div>
                        <p class="text-muted fs-11 mt-2 mb-0">Action immédiate requise</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="fs-12 fw-medium mb-1">En cours d'exécution</p>
                                <h4>@{{ stats.in_progress }}</h4>
                            </div>
                            <span class="avatar avatar-md bg-soft-info rounded-circle">
                                <i class="ti ti-settings-automation text-info"></i>
                            </span>
                        </div>
                        <p class="text-muted fs-11 mt-2 mb-0">Techniciens sur le terrain</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="fs-12 fw-medium mb-1">Terminées ce mois</p>
                                <h4>@{{ stats.completed }}</h4>
                            </div>
                            <span class="avatar avatar-md bg-soft-success rounded-circle">
                                <i class="ti ti-circle-check text-success"></i>
                            </span>
                        </div>
                        <p class="text-muted fs-11 mt-2 mb-0">Validées avec preuves</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Progression par Station -->
            <div class="col-xl-8 col-lg-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Progression par Site / Station</h5>
                        <div class="d-flex align-items-center gap-2">
                            <div class="flex-fill" style="min-width: 260px;">
                                <select class="form-control" id="filter-station-monitoring" multiple style="width: 100%;">
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-nowrap table-hover" id="monitoring-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Station</th>
                                        <th>Missions</th>
                                        <th>Progression Moyenne</th>
                                        <th>Statut Critique</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in stationProgress" :key="item.id">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="ti ti-map-pin text-muted me-2"></i>
                                                <span class="fw-bold">@{{ item.name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-dark">@{{ item.tasks_count }} tâche(s)</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center" style="min-width: 200px;">
                                                <div class="progress flex-fill me-2" style="height: 8px;">
                                                    <div :class="'progress-bar ' + getProgressClass(item.avg_progress)" :style="{ width: item.avg_progress + '%' }"></div>
                                                </div>
                                                <small class="fw-bold">@{{ item.avg_progress }}%</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span v-if="item.overdue_count > 0" class="badge badge-soft-danger animate-pulse">
                                                <i class="ti ti-alert-circle me-1"></i> @{{ item.overdue_count }} en retard
                                            </span>
                                            <span v-else class="text-success small"><i class="ti ti-check"></i> À jour</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flux des dernières preuves (Photos live) -->
            <div class="col-xl-4 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h5>Dernières Preuves Terrain</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-3 overflow-auto" style="max-height: 500px;">
                            <div v-for="ev in recentEvidences" :key="ev.id" class="mb-4 border-bottom pb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar avatar-sm me-2">
                                        <img :src="ev.agent.photo || '/assets/img/avatar.jpg'" class="rounded-circle" alt="agent">
                                    </div>
                                    <div class="overflow-hidden">
                                        <h6 class="fs-13 mb-0 text-truncate">@{{ ev.agent.fullname }}</h6>
                                        <small class="text-muted">@{{ ev.task.title }}</small>
                                    </div>
                                    <small class="ms-auto text-primary">@{{ formatTimeAgo(ev.created_at) }}</small>
                                </div>
                                <div class="position-relative">
                                    <img :src="ev.image_path" :data-zoom="ev.image_path" class="img-fluid rounded-3 mb-2 w-100" style="height: 180px; object-fit: cover; cursor: pointer;">
                                    <div v-if="ev.location" class="position-absolute bottom-0 end-0 m-2">
                                        <span class="badge bg-dark opacity-75 fs-10"><i class="ti ti-map-pin"></i> GPS</span>
                                    </div>
                                </div>
                                <p v-if="ev.note" class="fs-12 text-muted italic mb-0">
                                    <i class="ti ti-quote text-gray-3"></i> @{{ ev.note }}
                                </p>
                            </div>
                            <div v-if="recentEvidences.length === 0" class="text-center p-4 text-muted">
                                <i class="ti ti-camera-off fs-24 d-block mb-2"></i>
                                Aucune photo reçue aujourd'hui
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
<script>
    window.__STATIONS__ = @json($stations ?? []);
</script>
<script type="module" src="{{ asset('assets/js/scripts/tasks-monitoring.js') }}"></script>
@endpush
