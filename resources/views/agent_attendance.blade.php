@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>

        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Historique des Présences de l'agent : @{{ agent.fullname || '---' }}</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            RH
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Historique des présences</li>
                    </ol>
                </nav>
            </div>
        </div>
        <!-- /Breadcrumb -->

        <div class="row">
            <div class="col-xl-3 col-lg-4 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="mb-3 text-center">
                            <h6 class="fw-medium text-gray-5 mb-2">Profile Agent</h6>
                            <h4>@{{ agent.fullname || '---' }}</h4>
                        </div>
                        <div class="attendance-circle-progress mx-auto mb-3" :data-value="profileProgress">
                            <span class="progress-left">
                                <span class="progress-bar border-success"></span>
                            </span>
                            <span class="progress-right">
                                <span class="progress-bar border-success"></span>
                            </span>
                            <div class="avatar avatar-xxl avatar-rounded">
                                <img :src="agent.photo || '{{ asset("assets/img/profiles/avatar-27.jpg") }}'" alt="Img">
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="badge badge-md mb-3" :class="agentStatusBadgeClass">@{{ agentStatusText }}</div>
                            <h6 class="fw-medium d-flex align-items-center justify-content-center mb-3">
                                <i class="ti ti-fingerprint text-primary me-1"></i>
                                Arrivé à @{{ arrivedAtText }}
                            </h6>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-9 col-lg-8 d-flex">
                <div class="row flex-fill">
                    <div class="col-xl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="border-bottom mb-2 pb-2">
                                    <span class="avatar avatar-sm bg-primary mb-2"><i class="ti ti-clock-stop"></i></span>
                                    <h2 class="mb-2">@{{ stats.totalHoursPeriod }} <span class="fs-20 text-gray-5">h</span></h2>
                                    <p class="fw-medium text-truncate">Total heure (période)</p>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="border-bottom mb-2 pb-2">
                                    <span class="avatar avatar-sm bg-dark mb-2"><i class="ti ti-clock-up"></i></span>
                                    <h2 class="mb-2">@{{ stats.presences }} <span class="fs-20 text-gray-5">jours</span></h2>
                                    <p class="fw-medium text-truncate">Présences (période)</p>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <div class="border-bottom mb-2 pb-2">
                                    <span class="avatar avatar-sm bg-warning mb-2"><i class="ti ti-clock-exclamation"></i></span>
                                    <h2 class="mb-2">@{{ stats.retards }}</h2>
                                    <p class="fw-medium text-truncate">Retards (période)</p>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-4">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-dark-transparent me-1"></i>Horaire affecté</p>
                                            <h3>Nom de l'horaire</h3>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-success me-1"></i>Heure début</p>
                                            <h3>08h 36m</h3>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-warning me-1"></i>Heure fin</p>
                                            <h3>22m 15s</h3>
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="progress bg-transparent-dark mb-3" style="height: 24px;">
                                            <div class="progress-bar bg-white rounded" role="progressbar" style="width: 18%;"></div>
                                            <div class="progress-bar bg-success rounded me-2" role="progressbar" style="width: 18%;"></div>
                                            <div class="progress-bar bg-warning rounded me-2" role="progressbar" style="width: 5%;"></div>
                                            <div class="progress-bar bg-success rounded me-2" role="progressbar" style="width: 28%;"></div>
                                            <div class="progress-bar bg-warning rounded me-2" role="progressbar" style="width: 17%;"></div>
                                            <div class="progress-bar bg-success rounded me-2" role="progressbar" style="width: 22%;"></div>
                                            <div class="progress-bar bg-warning rounded me-2" role="progressbar" style="width: 5%;"></div>
                                            <div class="progress-bar bg-info rounded me-2" role="progressbar" style="width: 3%;"></div>
                                            <div class="progress-bar bg-info rounded" role="progressbar" style="width: 2%;"></div>
                                            <div class="progress-bar bg-white rounded" role="progressbar" style="width: 18%;"></div>
                                        </div>

                                    </div>
                                    <div class="co-md-12">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                                            <span class="fs-10">06:00</span>
                                            <span class="fs-10">07:00</span>
                                            <span class="fs-10">08:00</span>
                                            <span class="fs-10">09:00</span>
                                            <span class="fs-10">10:00</span>
                                            <span class="fs-10">11:00</span>
                                            <span class="fs-10">12:00</span>
                                            <span class="fs-10">01:00</span>
                                            <span class="fs-10">02:00</span>
                                            <span class="fs-10">03:00</span>
                                            <span class="fs-10">04:00</span>
                                            <span class="fs-10">05:00</span>
                                            <span class="fs-10">06:00</span>
                                            <span class="fs-10">07:00</span>
                                            <span class="fs-10">08:00</span>
                                            <span class="fs-10">09:00</span>
                                            <span class="fs-10">10:00</span>
                                            <span class="fs-10">11:00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                        <h5>Historique des pointages</h5>
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
                                    Statut
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end p-3">
                                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1" @click="filters.status = ''">Tous</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1" @click="filters.status = 'present'">Présent</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1" @click="filters.status = 'absent'">Absent</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1" @click="filters.status = 'late'">Retard</a></li>
                                </ul>
                            </div>
                            <button class="btn btn-white border" @click="load">Filtrer</button>
                            <span class="text-muted ms-2" v-if="isLoading">Chargement...</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" ref="table">
                                <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Station affectation</th>
                                    <th>Station check-in</th>
                                    <th>Station check-out</th>
                                    <th>Heure entrée</th>
                                    <th>Heure sortie</th>
                                    <th>Statut</th>
                                    <th>Retard</th>
                                    <th>Total heures</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="p in filteredRows" :key="p.id">
                                    <td>@{{ p.date_reference }}</td>
                                    <td>@{{ (p.assigned_station && p.assigned_station.name) ? p.assigned_station.name : '-' }}</td>
                                    <td>@{{ (p.station_check_in && p.station_check_in.name) ? p.station_check_in.name : '-' }}</td>
                                    <td>@{{ (p.station_check_out && p.station_check_out.name) ? p.station_check_out.name : '-' }}</td>
                                    <td>@{{ p.started_at || '--:--' }}</td>
                                    <td>@{{ p.ended_at || '--:--' }}</td>
                                    <td>
                                                <span class="badge badge-success-transparent d-inline-flex align-items-center" v-if="p.started_at && p.ended_at">
                                                    <i class="ti ti-point-filled me-1"></i>Présent
                                                </span>
                                        <span class="badge badge-warning-transparent d-inline-flex align-items-center" v-else-if="p.started_at">
                                                    <i class="ti ti-point-filled me-1"></i>En poste
                                                </span>
                                        <span class="badge badge-danger-transparent d-inline-flex align-items-center" v-else>
                                                    <i class="ti ti-point-filled me-1"></i>Absent
                                                </span>
                                    </td>
                                    <td>
                                                <span class="badge badge-warning d-inline-flex align-items-center" v-if="p.retard === 'oui'">
                                                    <i class="ti ti-clock-hour-11 me-1"></i>Oui
                                                </span>
                                        <span class="badge badge-success d-inline-flex align-items-center" v-else>
                                                    <i class="ti ti-clock-hour-11 me-1"></i>Non
                                                </span>
                                    </td>
                                    <td>
                                                <span class="badge badge-success d-inline-flex align-items-center">
                                                    <i class="ti ti-clock-hour-11 me-1"></i>@{{ p.duree || '--' }}
                                                </span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/agent-attendance.js") }}"></script>
@endpush

