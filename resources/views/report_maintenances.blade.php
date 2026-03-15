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
                        <li class="breadcrumb-item active" aria-current="page">Maintenances</li>
                    </ol>
                </nav>
            </div>

            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                <div class="me-3">
                    <div class="input-icon position-relative">
                        <span class="input-icon-addon">
                            <i class="ti ti-calendar text-gray-9"></i>
                        </span>
                        <input type="text" class="form-control date-range bookingrange" placeholder="dd/mm/yyyy - dd/mm/yyyy">
                    </div>
                </div>

                <div class="dropdown me-3">
                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                        <i class="ti ti-file-export me-1"></i>Exporter
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end p-3">
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1" @click.prevent="exportReport('excel')">
                                <i class="ti ti-file-type-xls me-1"></i>Exporter en Excel
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1" @click.prevent="exportReport('pdf')">
                                <i class="ti ti-file-type-pdf me-1"></i>Exporter en PDF
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="avatar avatar-md bg-primary-transparent"><i class="ti ti-tool"></i></div>
                            <span class="badge badge-sm badge-primary">Total</span>
                        </div>
                        <h2>@{{ summary.total }}</h2>
                        <p class="text-gray-5">Interventions</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="avatar avatar-md bg-success-transparent"><i class="ti ti-check"></i></div>
                            <span class="badge badge-sm badge-success">Terminées</span>
                        </div>
                        <h2>@{{ summary.completed }}</h2>
                        <p class="text-gray-5">Clôturées</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="avatar avatar-md bg-warning-transparent"><i class="ti ti-loader"></i></div>
                            <span class="badge badge-sm badge-warning">En cours</span>
                        </div>
                        <h2>@{{ summary.ongoing }}</h2>
                        <p class="text-gray-5">Actives</p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="avatar avatar-md bg-info-transparent"><i class="ti ti-map-pin"></i></div>
                            <span class="badge badge-sm badge-info">Sur Station</span>
                        </div>
                        <h2>@{{ summary.on_station }}</h2>
                        <p class="text-gray-5">Proximité OK</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Liste des interventions</h5>
                <div class="d-flex align-items-center flex-wrap row-gap-3">
                    <div class="me-3" style="width: 200px;">
                        <select class="form-select" v-model="filters.station_id" ref="stationSelect">
                            <option value="">Toutes les stations</option>
                            <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                        </select>
                    </div>
                    <div class="me-3" style="width: 200px;">
                        <select class="form-select" v-model="filters.agent_id" ref="agentSelect">
                            <option value="">Tous les agents</option>
                            <option v-for="a in agents" :key="a.id" :value="a.id">@{{ a.fullname }}</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" @click="load" :disabled="isLoading">
                        <i class="ti ti-refresh me-1"></i>Actualiser
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-nowrap mb-0" ref="table">
                        <thead class="thead-light">
                            <tr>
                                <th>Agent</th>
                                <th>Station</th>
                                <th>Date</th>
                                <th>Début</th>
                                <th>Fin</th>
                                <th>Distance</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in rows" :key="m.id">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm me-2">
                                            <img v-if="m.agent && m.agent.photo" :src="m.agent.photo" class="rounded-circle" alt="Img">
                                            <img v-else src="{{ asset('assets/img/avatar.jpg') }}" class="rounded-circle" alt="Img">
                                        </div>
                                        <div>
                                            <h6 class="fw-medium mb-0">@{{ m.agent ? m.agent.fullname : '---' }}</h6>
                                            <small class="text-muted">@{{ m.agent ? m.agent.matricule : '---' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>@{{ m.station ? m.station.name : '---' }}</td>
                                <td>@{{ m.date_maintenance_iso || m.date_maintenance }}</td>
                                <td><span class="badge badge-soft-success">@{{ m.started_at || '--:--' }}</span></td>
                                <td><span class="badge badge-soft-dark">@{{ m.end_at || '--:--' }}</span></td>
                                <td>
                                    <span v-if="m.is_on_station === true" class="text-success">
                                        <i class="ti ti-circle-check me-1"></i>@{{ m.distance_label }}
                                    </span>
                                    <span v-else-if="m.is_on_station === false" class="text-danger">
                                        <i class="ti ti-circle-x me-1"></i>@{{ m.distance_label }}
                                    </span>
                                    <span v-else class="text-muted">@{{ m.distance_label }}</span>
                                </td>
                                <td>
                                    <span v-if="m.end_at" class="badge badge-success-transparent">Clôturée</span>
                                    <span v-else class="badge badge-warning-transparent">En cours</span>
                                </td>
                                <td class="text-end">
                                    <a href="javascript:void(0);" class="btn btn-icon btn-sm btn-white" @click="openDetails(m)">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Détails -->
        <div class="modal fade" id="maintenanceDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content" v-if="selectedMaintenance">
                    <div class="modal-header">
                        <h5 class="modal-title">Détails de l'intervention</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <label class="text-muted mb-1">Photo début</label>
                                    <div class="bg-light rounded overflow-hidden" style="height: 250px;">
                                        <img v-if="selectedMaintenance.photo_debut" :src="selectedMaintenance.photo_debut" class="w-100 h-100 object-fit-cover" :data-zoom="selectedMaintenance.photo_debut">
                                        <div v-else class="d-flex align-items-center justify-content-center h-100 text-muted">
                                            Aucune photo
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded">
                                    <label class="text-muted mb-1">Photo fin</label>
                                    <div class="bg-light rounded overflow-hidden" style="height: 250px;">
                                        <img v-if="selectedMaintenance.photo_fin" :src="selectedMaintenance.photo_fin" class="w-100 h-100 object-fit-cover" :data-zoom="selectedMaintenance.photo_fin">
                                        <div v-else class="d-flex align-items-center justify-content-center h-100 text-muted">
                                            Aucune photo
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 border rounded">
                                    <h6 class="mb-2">Commentaires / Traces Géo</h6>
                                    <p class="mb-0 text-break" style="white-space: pre-wrap;">@{{ selectedMaintenance.commentaire || 'Aucun commentaire.' }}</p>
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
