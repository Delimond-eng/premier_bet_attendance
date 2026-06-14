@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>

        <!-- Breadcrumb -->
        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Vue globale</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="https://smarthr.co.in/demo/html/template/index.html"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            TBD
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Vue globale</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-3 mb-2">
                <div style="min-width: 150px;">
                    <select class="form-select" v-model="range.mode" @change="applyMode">
                        <option value="today">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                        <option value="custom">Personnalisé</option>
                    </select>
                </div>
                <div class="input-icon position-relative">
                    <span class="input-icon-addon">
                        <i class="ti ti-calendar text-gray-9"></i>
                    </span>
                    <input type="text" class="form-control date-range bookingrange" placeholder="dd/mm/yyyy - dd/mm/yyyy">
                </div>
            </div>
        </div>
        <!-- /Breadcrumb -->

        <!-- start row -->
        <div class="row">

            <div class="col-xl-5 d-flex flex-column">
                <div class="card flex-fill mb-3">
                    <div class="card-body">
                        <div class="border rounded border-start border-start-primary d-flex align-items-center justify-content-between p-2 gap-2 flex-wrap mb-3">
                            <h2 class="card-title mb-0">Status Présences Agent</h2>
                            <a :href="detailsUrl" class="btn btn-md btn-light" @click.prevent="goDetails">Voir détails</a>
                        </div>
                        <div id="status-chart" class="mb-3" v-pre></div>
                        <div class="row">
                            <div class="col-4">
                                <div class="text-center">
                                    <h3 class="main-title mb-1">@{{ counts.presences }}</h3>
                                    <p class="d-inline-flex align-items-center mb-0"><span class="chart-line bg-primary me-1"></span>Présents</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <h3 class="main-title mb-1">@{{ counts.retards }}</h3>
                                    <p class="d-inline-flex align-items-center mb-0"><span class="chart-line bg-secondary me-1"></span>Retards</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center">
                                    <h3 class="main-title mb-1">@{{ counts.absents }}</h3>
                                    <p class="d-inline-flex align-items-center mb-0"><span class="chart-line bg-light me-1"></span>Absents</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card flex-fill">
                    <div class="card-body pb-sm-2">
                        <div class="border rounded border-start border-start-primary d-flex align-items-center justify-content-between p-2 gap-2 flex-wrap mb-3">
                            <h2 class="card-title mb-0">Congés & Autorisations</h2>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="border btn btn-white btn-md d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                    <i class="ti ti-calendar-due me-1 fs-14"></i>Mensuelle
                                </a>
                                <ul class="dropdown-menu mt-2 p-3">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Mensuelle
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Hebdomadaire
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Aujourd'hui
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-5">
                                <div id="leave-chart" v-pre></div>
                            </div>
                            <div class="col-sm-7">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <p class="d-inline-flex align-items-center text-dark mb-0"><i class="ti ti-circle-filled text-primary-900 fs-7 me-1"></i>Autorisations spéciales (approuvées)</p>
                                        <span class="badge fw-normal bg-light text-dark border rounded-pill fs-13">@{{ authorizations.speciales }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <p class="d-inline-flex align-items-center text-dark mb-0"><i class="ti ti-circle-filled text-primary-800 fs-7 me-1"></i>Cong&eacute;s (jours approuvés)</p>
                                        <span class="badge fw-normal bg-light text-dark border rounded-pill fs-13">@{{ authorizations.conges }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-7">
                <div class="card">
                    <div class="card-body">
                        <div class="border rounded border-start border-start-primary d-flex align-items-center justify-content-between p-2 gap-2 flex-wrap mb-3">
                            <h2 class="card-title mb-0">Stats globales</h2>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="border btn btn-white btn-md d-inline-flex align-items-center" 									data-bs-toggle="dropdown">
                                    <i class="ti ti-calendar-due me-1 fs-14"></i>Mensuelle
                                </a>
                                <ul class="dropdown-menu mt-2 p-3">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Mensuelle
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Hebdomadaire
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Aujourd'hui
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 d-flex">
                                <div class="card shadow-none mb-0 flex-fill">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar avatar-lg bg-primary rounded-circle flex-shrink-0">
                                                <i class="ti ti-users-group text-white fs-24"></i>
                                            </div>
                                            <div class="ms-2">
                                                <p class="fw-semibold text-truncate mb-0">Total Agents</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="main-title mb-1">@{{ counts.agents }}</h3>
                                                <p class="fs-13 mb-0">Toutes les stations</p>
                                            </div>

                                        </div>
                                    </div> <!-- end card -->
                                </div> <!-- end card body -->
                            </div> <!-- end col -->

                            <div class="col-md-6 d-flex">
                                <div class="card shadow-none mb-0 flex-fill">
                                    <div class="card-body">
                                        <div class="d-flex avatar-lg align-items-center mb-3">
                                            <div class="avatar bg-success rounded-circle flex-shrink-0">
                                                <i class="ti ti-clock-check text-white fs-24"></i>
                                            </div>
                                            <div class="ms-2">
                                                <p class="fw-semibold text-truncate mb-0">Présents</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="main-title mb-1">@{{ counts.presences }}</h3>
                                                <p class="fs-13 mb-0">Toutes les stations</p>
                                            </div>
                                        </div>
                                    </div> <!-- end card -->
                                </div> <!-- end card body -->
                            </div> <!-- end col -->

                            <div class="col-md-6 d-flex">
                                <div class="card shadow-none mb-0 flex-fill">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar avatar-lg bg-warning rounded-circle flex-shrink-0">
                                                <i class="ti ti-clock-exclamation text-white fs-24"></i>
                                            </div>
                                            <div class="ms-2">
                                                <p class="fw-semibold text-truncate mb-0">Arrivés en retard</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="main-title mb-1">@{{ counts.retards }}</h3>
                                                <p class="fs-13 mb-0">Toutes les stations</p>
                                            </div>
                                        </div>
                                    </div> <!-- end card -->
                                </div> <!-- end card body -->
                            </div> <!-- end col -->

                            <div class="col-md-6 d-flex">
                                <div class="card shadow-none mb-0 flex-fill">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar avatar-lg bg-danger rounded-circle flex-shrink-0">
                                                <i class="ti ti-clock-x text-white fs-24"></i>
                                            </div>
                                            <div class="ms-2">
                                                <p class="fw-semibold text-truncate mb-0">Absents</p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="main-title mb-1">@{{ counts.absents }}</h3>
                                                <p class="fs-13 mb-0">Toutes les stations</p>
                                            </div>
                                        </div>
                                    </div> <!-- end card -->
                                </div> <!-- end card body -->
                            </div> <!-- end col -->

                        </div>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        <!-- start row -->
        <div class="row">

            <div class="col-xxl-8 d-flex">
                <div class="card flex-fill">
                    <div class="card-body pb-0">
                        <div class="border rounded border-start border-start-primary d-flex align-items-center justify-content-between p-2 gap-2 flex-wrap mb-3">
                            <h2 class="card-title mb-0">Graphique d’évolution des présences</h2>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="border btn btn-white btn-md d-inline-flex align-items-center" 									data-bs-toggle="dropdown">
                                    <i class="ti ti-calendar-due me-1 fs-14"></i>Hebdomadaire
                                </a>
                                <ul class="dropdown-menu mt-2 p-3">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Mensuelle
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Hebdomadaire
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Aujourd'hui
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                            <div class="d-flex align-items-center flex-wrap gap-3">
                                <div class="d-flex align-items-center pe-3 border-end">
                                    <h3 class="mb-0">@{{ Math.max((counts.presences || 0) - (counts.retards || 0), 0) }}<span class="ms-2 fw-normal fs-14 text-default">Arrivée à l’heure</span></h3>
                                </div>
                                <div class="d-flex align-items-center pe-3 border-end">
                                    <h3 class="mb-0">@{{ counts.retards }}<span class="ms-2 fw-normal fs-14 text-default">Retard</span></h3>
                                </div>
                                <div class="d-flex align-items-center">
                                    <h3 class="mb-0">@{{ counts.absents }}<span class="ms-2 fw-normal fs-14 text-default">Absent</span></h3>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <p class="mb-0"><i class="ti ti-square-rounded-filled text-primary fs-15 me-1"></i>Present</p>
                                <p class="mb-0"><i class="ti ti-square-rounded-filled text-secondary fs-15 me-1"></i>Retard</p>
                                <p class="mb-0"><i class="ti ti-square-rounded-filled text-warning fs-15 me-1"></i>Absent</p>
                            </div>
                        </div>
                        <div class="d-sm-flex align-items-center flex-sm-row flex-column">
                            <div id="attendance-chart" class="w-100" v-pre>
                                <canvas id="attendance-chart-js" height="180"></canvas>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="border p-3 rounded text-center mb-3">
                                    <p class="mb-1">Nombre d’heures travaillées</p>
                                    <h3 class="main-title mb-0">@{{ weeklyKpis.worked_hours }} h</h3>
                                </div>
                                <div class="border p-3 rounded text-center mb-3">
                                    <p class="mb-1">Pointages manqués</p>
                                    <h3 class="main-title mb-0">@{{ weeklyKpis.missed_punches }}</h3>
                                </div>
                                <div class="border p-3 rounded text-center mb-3">
                                    <p class="mb-1">Moyenne hebdomadaire</p>
                                    <h3 class="main-title mb-0">@{{ weeklyKpis.weekly_average }}%</h3>
                                </div>
                            </div>
                        </div>

                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xxl-4 col-xl-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="border rounded border-start border-start-primary d-flex align-items-center justify-content-between p-2 gap-2 flex-wrap mb-3">
                            <h2 class="card-title mb-0">(@{{ latestCheckins.length }}) Derniers arrivées en temps réel</h2>

                        </div>
                        <div v-if="isLoading" class="p-2 bg-light rounded border d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted">Chargement...</div>
                        </div>
                        <div v-else-if="latestCheckins.length === 0" class="p-2 bg-light rounded border d-flex align-items-center justify-content-between mb-2">
                            <div class="text-muted">Aucun pointage trouvé.</div>
                        </div>
                        <div v-for="item in latestCheckins.slice(0, 5)" :key="item.id" class="p-2 bg-light rounded border-bottom d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar flex-shrink-0">
                                        <img v-if="item.agent.photo" :src="item.agent?.photo ?? '{{asset("assets/img/avatar.jpg")}}'" :data-zoom="item.agent?.photo" class="rounded-circle" alt="user">
                                    </a>
                                    <div class="ms-2">
                                        <p class="fs-14 fw-medium text-truncate mb-1"><a href="#">@{{ item.agent?.fullname ?? 'Agent' }}</a></p>
                                        <p class="fs-13">@{{ item.station_check_in?.name ?? item.assigned_station?.name ?? 'Station' }}</p>
                                    </div>
                                </div>
                                <div>
                                    <p class="fs-13 text-dark mb-1">@{{ item.started_at ?? '--:--' }}</p>
                                    <span class="badge badge-danger-transparent rounded-pill" v-if="item.retard === 'oui'">Retard</span>
                                    <span class="badge badge-success-transparent rounded-pill" v-else>À l'heure</span>
                                </div>
                            </div>


                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->

        @if(str_contains(request()->getHost(), 'electrocool'))
        <div class="row mt-2">
            <div class="col-xxl-4 col-xl-5 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="border rounded border-start border-start-info d-flex align-items-center justify-content-between p-2 gap-2 flex-wrap mb-3">
                            <h2 class="card-title mb-0">Situation globale maintenance</h2>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <small class="text-muted">Total</small>
                                    <h4 class="mb-0">@{{ maintenances.summary.total || 0 }}</h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <small class="text-muted">Cloturees</small>
                                    <h4 class="mb-0 text-success">@{{ maintenances.summary.completed || 0 }}</h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <small class="text-muted">En cours</small>
                                    <h4 class="mb-0 text-warning">@{{ maintenances.summary.ongoing || 0 }}</h4>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded border h-100">
                                    <small class="text-muted">Sur station</small>
                                    <h4 class="mb-0 text-info">@{{ maintenances.summary.on_station || 0 }}</h4>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded border h-100">
                                    <small class="text-muted">Hors station</small>
                                    <h4 class="mb-0 text-danger">@{{ maintenances.summary.off_station || 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-8 col-xl-7 d-flex">
                <div class="card flex-fill">
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
                                    <img v-else src="{{asset("assets/img/avatar.jpg")}}" class="rounded-circle" alt="agent">
                                </a>
                                <div class="ms-2">
                                    <p class="fs-14 fw-medium text-truncate mb-1">@{{ m.agent?.fullname ?? 'Agent' }}</p>
                                    <p class="fs-13 mb-0">@{{ m.station?.name ?? 'Station' }}</p>
                                    <small class="text-muted">@{{ m.started_at || '--:--' }} - @{{ m.end_at || '--:--' }}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <p class="fs-12 text-muted mb-1">@{{ m.distance_label || 'Distance indisponible' }}</p>
                                <button class="btn btn-sm btn-outline-primary" @click="openMaintenanceDetails(m)">Voir details</button>
                            </div>
                        </div>
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
        @endif

    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/dashboard.js") . '?v=' . filemtime(public_path('assets/js/scripts/dashboard.js')) }}"></script>
@endpush
