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
                            <a href="#" class="btn btn-md btn-light">Voir détails</a>
                        </div>
                        <div id="status-chart" class="mb-3"></div>
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
                            <h2 class="card-title mb-0">Autorisation spéciale</h2>
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
                                <div id="leave-chart"></div>
                            </div>
                            <div class="col-sm-7">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <p class="d-inline-flex align-items-center text-dark mb-0"><i class="ti ti-circle-filled text-primary-900 fs-7 me-1"></i>Malades</p>
                                        <span class="badge fw-normal bg-light text-dark border rounded-pill fs-13">@{{ authorizations.maladies }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <p class="d-inline-flex align-items-center text-dark mb-0"><i class="ti ti-circle-filled text-primary-800 fs-7 me-1"></i>Congés</p>
                                        <span class="badge fw-normal bg-light text-dark border rounded-pill fs-13">@{{ authorizations.conges }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <p class="d-inline-flex align-items-center text-dark mb-0"><i class="ti ti-circle-filled text-primary-700 fs-7 me-1"></i>Autres</p>
                                        <span class="badge fw-normal bg-light text-dark border rounded-pill fs-13">@{{ authorizations.autres }}</span>
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
                            <div id="attendance-chart" class="w-100">
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
                                        <img v-if="item.agent.photo" :src="item.agent?.photo" class="rounded-circle" alt="user">
                                        <img v-else src="{{asset("assets/img/avatar.jpg")}}" class="rounded-circle" alt="user">
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

    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/dashboard.js") }}"></script>
@endpush
