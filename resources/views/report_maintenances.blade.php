@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Rapport des maintenances</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Rapports</li>
                        <li class="breadcrumb-item active" aria-current="page">Maintenance</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Station</label>
                        <select class="form-select" v-model="filters.station_id" ref="stationSelect">
                            <option value="">Toutes les stations</option>
                            <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Agent</label>
                        <select class="form-select" v-model="filters.agent_id" ref="agentSelect">
                            <option value="">Tous les agents</option>
                            <option v-for="a in agents" :key="a.id" :value="a.id">@{{ a.fullname }} (@{{ a.matricule }})</option>
                        </select>
                    </div>
                    <div class="col-xl-4 col-md-8">
                        <label class="form-label">Periode</label>
                        <div class="input-icon position-relative">
                            <span class="input-icon-addon">
                                <i class="ti ti-calendar text-gray-9"></i>
                            </span>
                            <input type="text" class="form-control date-range bookingrange" placeholder="dd/mm/yyyy - dd/mm/yyyy">
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-4 d-grid">
                        <button class="btn btn-primary" @click="load" :disabled="isLoading">
                            @{{ isLoading ? 'Chargement...' : 'Filtrer' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-none ticket-card bg-dark-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">Total maintenances</p>
                        <h2 class="mb-0">@{{ summary.total }}</h2>
                    </div>
                    <span class="bg-dark"></span>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card shadow-none ticket-card bg-success-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">Maintenances cloturees</p>
                        <h2 class="mb-0">@{{ summary.completed }}</h2>
                    </div>
                    <span class="bg-success"></span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card shadow-none ticket-card bg-warning-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">En cours</p>
                        <h2 class="mb-0">@{{ summary.ongoing }}</h2>
                    </div>
                    <span class="bg-warning"></span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card shadow-none ticket-card bg-info-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">Sur station</p>
                        <h2 class="mb-0">@{{ summary.on_station }}</h2>
                    </div>
                    <span class="bg-info"></span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4">
                <div class="card shadow-none ticket-card bg-danger-transparent card-3">
                    <div class="card-body">
                        <p class="mb-1 text-gray-6">Hors station</p>
                        <h2 class="mb-0">@{{ summary.off_station }}</h2>
                    </div>
                    <span class="bg-danger"></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">Historique des maintenances</h5>
                <span class="text-muted">@{{ rows.length }} enregistrement(s)</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" ref="table">
                        <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Agent</th>
                            <th>Station</th>
                            <th>Debut</th>
                            <th>Fin</th>
                            <th>Distance</th>
                            <th>Statut</th>
                            <th>Details</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="m in rows" :key="m.id">
                            <td>@{{ m.date_maintenance_iso || m.date_maintenance }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img v-if="m.agent?.photo" :src="m.agent.photo" :data-zoom="m.agent.photo" class="rounded-circle" alt="agent">
                                        <img v-else src="{{ asset('assets/img/avatar.jpg') }}" class="rounded-circle" alt="agent">
                                    </span>
                                    <div>
                                        <h6 class="mb-0">@{{ m.agent?.fullname || '-' }}</h6>
                                        <small class="text-muted">@{{ m.agent?.matricule || '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>@{{ m.station?.name || '-' }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img v-if="m.photo_debut" :src="m.photo_debut" :data-zoom="m.photo_debut" class="rounded-circle" alt="photo debut">
                                        <img v-else src="{{ asset('assets/img/avatar.jpg') }}" class="rounded-circle" alt="photo debut">
                                    </span>
                                    <span class="badge badge-soft-success">@{{ m.started_at || '--:--' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img v-if="m.photo_fin" :src="m.photo_fin" :data-zoom="m.photo_fin" class="rounded-circle" alt="photo fin">
                                        <img v-else src="{{ asset('assets/img/avatar.jpg') }}" class="rounded-circle" alt="photo fin">
                                    </span>
                                    <span class="badge badge-soft-dark">@{{ m.end_at || '--:--' }}</span>
                                </div>
                            </td>
                            <td>@{{ m.distance_label || 'Distance indisponible' }}</td>
                            <td>
                                <span v-if="m.end_at" class="badge badge-success-transparent">Cloturee</span>
                                <span v-else class="badge badge-warning-transparent">En cours</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" @click="openDetails(m)">
                                    Voir details
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
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
                                    <p class="mb-0 text-muted">Coordonnees: @{{ selectedMaintenance.latlng || '-' }}</p>
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
    <script type="module" src="{{ asset("assets/js/scripts/report-maintenances.js") . '?v=' . filemtime(public_path('assets/js/scripts/report-maintenances.js')) }}"></script>
@endpush
