@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Rapports des présences</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Rapports</li>
                        <li class="breadcrumb-item active" aria-current="page">Journalier</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                <div class="flex-fill mb-2" style="width: 260px;">
                    <select class="form-select" v-model="filters.station_id" ref="stationSelect">
                        <option value="">Toutes les stations</option>
                        <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                    </select>
                </div>
                <div class="me-2 mb-2">
                    <input type="date" class="form-control" v-model="filters.date">
                </div>
                <div class="me-2 mb-2">
                    <button class="btn btn-primary" @click="load">Charger</button>
                </div>
                <div class="mb-2">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ti ti-file-export me-1"></i>Exporter
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-3">
                            <li>
                                <a class="dropdown-item rounded-1" :href="exportExcelUrl" target="_blank">Exporter en Excel</a>
                            </li>
                            <li>
                                <a class="dropdown-item rounded-1" :href="exportPdfUrl" target="_blank">Exporter en PDF</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Breadcrumb -->

        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="card shadow-none ticket-card bg-dark-transparent card-3">
                    <div class="card-body">
                        <div class="d-inline-flex flex-column gap-3">
                            <div class="avatar avatar-lg bg-dark rounded-3 flex-shrink-0">
                                <i class="ti ti-users text-white fs-24"></i>
                            </div>
                            <p class="mb-0 text-gray-6">Agents</p>
                            <h2 class="mb-0">@{{ count.agents }}</h2>
                        </div>
                    </div>
                    <span class="bg-dark"></span>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card shadow-none ticket-card bg-success-transparent card-3">
                    <div class="card-body">
                        <div class="d-inline-flex flex-column gap-3">
                            <div class="avatar avatar-lg bg-success rounded-3 flex-shrink-0">
                                <i class="ti ti-clock-check text-white fs-24"></i>
                            </div>
                            <p class="mb-0 text-gray-6">Présences</p>
                            <h2 class="mb-0">@{{ count.presences }}</h2>
                        </div>
                    </div>
                    <span class="bg-success"></span>
                </div>

            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card shadow-none ticket-card bg-warning-transparent card-3">
                    <div class="card-body">
                        <div class="d-inline-flex flex-column gap-3">
                            <div class="avatar avatar-lg bg-warning rounded-3 flex-shrink-0">
                                <i class="ti ti-clock-exclamation text-white fs-24"></i>
                            </div>
                            <p class="mb-0 text-gray-6">Retards</p>
                            <h2 class="mb-0">@{{ count.retards }}</h2>
                        </div>
                    </div>
                    <span class="bg-warning"></span>
                </div>

            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card shadow-none ticket-card bg-danger-transparent card-3">
                    <div class="card-body">
                        <div class="d-inline-flex flex-column gap-3">
                            <div class="avatar avatar-lg bg-danger rounded-3 flex-shrink-0">
                                <i class="ti ti-clock-x text-white fs-24"></i>
                            </div>
                            <p class="mb-0 text-gray-6">Absences</p>
                            <h2 class="mb-0">@{{ count.absents }}</h2>
                        </div>
                    </div>
                    <span class="bg-danger"></span>
                </div>

            </div>

        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Liste des pointages</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" v-if="isLoading">Chargement...</span>
                </div>
            </div>
            <div class="card-body">
                <div v-if="grouped.length === 0" class="text-muted">Aucune donnée.</div>

                <div v-for="g in grouped" :key="g.key" class="border-bottom mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h2 class="card-title d-flex align-items-center gap-2 mb-0"><i class="ti ti-home-bolt text-primary fs-16"></i>@{{ g.station_name }}</h2>

                        <div class="d-flex gap-2 align-items-center">
                            <div class="active-user-item">
                                <div class="avatar avatar-md bg-success rounded"> <i class="ti ti-clock-check fs-16"></i> </div>
                                <p class="fs-12 mb-0">Présence  <span class="fs-14 fw-semibold text-dark ms-1">@{{ g.stats.presences }}</span> </p>
                            </div>

                            <div class="active-user-item">
                                <div class="avatar avatar-md bg-warning rounded"> <i class="ti ti-clock-exclamation fs-16"></i> </div>
                                <p class="fs-12 mb-0">Retard  <span class="fs-14 fw-semibold text-dark ms-1">@{{ g.stats.retards }}</span> </p>
                            </div>

                            <div class="active-user-item">
                                <div class="avatar avatar-md bg-danger rounded"> <i class="ti ti-clock-x fs-16"></i> </div>
                                <p class="fs-12 mb-0">Absence  <span class="fs-14 fw-semibold text-dark ms-1">@{{ g.stats.absents }}</span> </p>
                            </div>
                            <span class="text-muted fs-12">@{{ g.rows.length }} ligne(s)</span>
                        </div>

                    </div>
                    <div class="table-responsive">
                        <table class="table" ref="tables">
                            <thead class="thead-light">
                            <tr>
                                <th>Date</th>
                                <th>Agent</th>
                                <th>Affectation</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Heure entrée</th>
                                <th>Heure sortie</th>
                                <th>Retard</th>
                                <th>Durée</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="row in g.rows" :key="row.id">
                                <td>@{{ row.date_reference }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm me-2">
                                            <img :src="row.agent?.photo || '{{asset("assets/img/avatar.jpg")}}'" class="rounded-circle" alt="img">
                                        </span>
                                        <div>
                                            <h6 class="mb-0">@{{ row.agent?.fullname ?? '-' }}</h6>
                                            <small class="text-muted">@{{ row.agent?.matricule ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>@{{ row.assigned_station?.name ?? '-' }}</td>
                                <td>@{{ row.station_check_in?.name ?? '-' }}</td>
                                <td>@{{ row.station_check_out?.name ?? '-' }}</td>
                                <td><span class="badge badge-success">@{{ row.started_at ?? '--:--' }}</span></td>
                                <td><span class="badge badge-purple">@{{ row.ended_at ?? '--:--' }}</span></td>
                                <td>
                                    <span class="badge badge-soft-danger" v-if="row.retard === 'oui'">Oui</span>
                                    <span class="badge badge-soft-success" v-else>Non</span>
                                </td>
                                <td><span class="badge badge-info">@{{ row.duree ?? '--' }}</span></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/report-presences.js") }}"></script>
@endpush
