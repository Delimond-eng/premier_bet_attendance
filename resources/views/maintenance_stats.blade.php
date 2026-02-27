@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Statistiques de maintenance</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Tableau de bord</li>
                        <li class="breadcrumb-item active" aria-current="page">Maintenance</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-3 mb-2">
                <div style="min-width: 160px;">
                    <select class="form-select" v-model="range.mode" @change="applyMode">
                        <option value="today">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                        <option value="custom">Personnalise</option>
                    </select>
                </div>
                <div class="input-icon position-relative">
                    <span class="input-icon-addon">
                        <i class="ti ti-calendar text-gray-9"></i>
                    </span>
                    <input type="text" class="form-control date-range bookingrange" placeholder="dd/mm/yyyy - dd/mm/yyyy">
                </div>
                <button class="btn btn-outline-info" @click="refresh" :disabled="isLoading">
                    @{{ isLoading ? 'Chargement...' : 'Actualiser' }}
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-none ticket-card bg-dark-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">Total maintenances</p>
                        <h2 class="mb-0">@{{ maintenances.summary.total || 0 }}</h2>
                    </div>
                    <span class="bg-dark"></span>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-none ticket-card bg-success-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">Cloturees</p>
                        <h2 class="mb-0">@{{ maintenances.summary.completed || 0 }}</h2>
                    </div>
                    <span class="bg-success"></span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card shadow-none ticket-card bg-warning-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">En cours</p>
                        <h2 class="mb-0">@{{ maintenances.summary.ongoing || 0 }}</h2>
                    </div>
                    <span class="bg-warning"></span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card shadow-none ticket-card bg-info-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">Sur station</p>
                        <h2 class="mb-0">@{{ maintenances.summary.on_station || 0 }}</h2>
                    </div>
                    <span class="bg-info"></span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card shadow-none ticket-card bg-danger-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">Hors station</p>
                        <h2 class="mb-0">@{{ maintenances.summary.off_station || 0 }}</h2>
                    </div>
                    <span class="bg-danger"></span>
                </div>
            </div>
        </div>

        <div class="card mt-2">
            <div class="card-body">
                <div class="border rounded border-start border-start-info d-flex align-items-center justify-content-between p-2 gap-2 flex-wrap mb-3">
                    <h2 class="card-title mb-0">(@{{ (maintenances.latest || []).length }}) 10 dernieres maintenances</h2>
                </div>
                <div v-if="isLoading" class="p-2 bg-light rounded border d-flex align-items-center justify-content-between mb-2">
                    <div class="text-muted">Chargement...</div>
                </div>
                <div v-else-if="!maintenances.latest || maintenances.latest.length === 0" class="p-2 bg-light rounded border d-flex align-items-center justify-content-between mb-2">
                    <div class="text-muted">Aucune maintenance trouvee.</div>
                </div>
                <div v-for="m in (maintenances.latest || [])" :key="'m-' + m.id" class="p-2 bg-light rounded border d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center">
                        <a href="javascript:void(0);" class="avatar flex-shrink-0">
                            <img v-if="m.agent?.photo" :src="m.agent.photo" :data-zoom="m.agent.photo" class="rounded-circle" alt="agent">
                            <img v-else src="{{ asset('assets/img/avatar.jpg') }}" class="rounded-circle" alt="agent">
                        </a>
                        <div class="ms-2">
                            <p class="fs-14 fw-medium text-truncate mb-1">@{{ m.agent?.fullname ?? 'Agent' }}</p>
                            <p class="fs-13 mb-0">@{{ m.station?.name ?? 'Station' }}</p>
                            <small class="text-muted">@{{ m.started_at || '--:--' }} - @{{ m.end_at || '--:--' }}</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <p class="fs-12 text-muted mb-1">@{{ m.distance_label || 'Distance indisponible' }}</p>
                        <button class="btn btn-sm btn-outline-primary" @click="openDetails(m)">Voir details</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="maintenanceDetailsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">Details maintenance</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" v-if="selectedMaintenance">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100">
                                    <h6 class="mb-2">Agent</h6>
                                    <p class="mb-1 fw-semibold">@{{ selectedMaintenance.agent?.fullname || '-' }}</p>
                                    <p class="mb-0 text-muted">Matricule: @{{ selectedMaintenance.agent?.matricule || '-' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100">
                                    <h6 class="mb-2">Station</h6>
                                    <p class="mb-1 fw-semibold">@{{ selectedMaintenance.station?.name || '-' }}</p>
                                    <p class="mb-0 text-muted">Coordonnees station: @{{ selectedMaintenance.latlng || '-' }}</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded h-100">
                                    <small class="text-muted">Date</small>
                                    <h6 class="mb-0">@{{ selectedMaintenance.date_maintenance_iso || selectedMaintenance.date_maintenance || '-' }}</h6>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded h-100">
                                    <small class="text-muted">Heure debut</small>
                                    <h6 class="mb-0">@{{ selectedMaintenance.started_at || '--:--' }}</h6>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded h-100">
                                    <small class="text-muted">Heure fin</small>
                                    <h6 class="mb-0">@{{ selectedMaintenance.end_at || '--:--' }}</h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100">
                                    <small class="text-muted">Distance par rapport a la station</small>
                                    <h5 class="mb-1">@{{ selectedMaintenance.distance_label || 'Distance indisponible' }}</h5>
                                    <span v-if="selectedMaintenance.is_on_station === true" class="badge badge-success-transparent">Sur station</span>
                                    <span v-else-if="selectedMaintenance.is_on_station === false" class="badge badge-danger-transparent">Hors station</span>
                                    <span v-else class="badge badge-light text-dark">Non determine</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100">
                                    <small class="text-muted">Commentaire</small>
                                    <p class="mb-0 text-dark">@{{ selectedMaintenance.commentaire || '-' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100">
                                    <small class="text-muted d-block mb-2">Photo debut</small>
                                    <img v-if="selectedMaintenance.photo_debut" :src="selectedMaintenance.photo_debut" :data-zoom="selectedMaintenance.photo_debut" class="img-fluid rounded border" alt="photo debut">
                                    <div v-else class="text-muted">Aucune photo</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded h-100">
                                    <small class="text-muted d-block mb-2">Photo fin</small>
                                    <img v-if="selectedMaintenance.photo_fin" :src="selectedMaintenance.photo_fin" :data-zoom="selectedMaintenance.photo_fin" class="img-fluid rounded border" alt="photo fin">
                                    <div v-else class="text-muted">Aucune photo</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/maintenance-stats.js") . '?v=' . filemtime(public_path('assets/js/scripts/maintenance-stats.js')) }}"></script>
@endpush
