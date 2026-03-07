@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>

        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Historique agent : @{{ agent.fullname || '---' }}</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">RH</li>
                        <li class="breadcrumb-item active" aria-current="page">Historique presence & maintenance</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-lg-4 d-flex">
                <div class="card flex-fill">
                    <div class="card-body">
                        <div class="mb-3 text-center">
                            <h6 class="fw-medium text-gray-5 mb-2">Profil agent</h6>
                            <h4>@{{ agent.fullname || '---' }} <small>(@{{ agent.matricule || '---' }})</small></h4>
                        </div>
                        <div class="attendance-circle-progress mx-auto mb-3" :data-value="profileProgress">
                            <span class="progress-left">
                                <span class="progress-bar border-success"></span>
                            </span>
                            <span class="progress-right">
                                <span class="progress-bar border-success"></span>
                            </span>
                            <div class="avatar avatar-xxl avatar-rounded">
                                <img v-if="agent.photo" :src="agent.photo" :data-zoom="agent.photo" alt="Img">
                                <img v-else src="{{ asset('assets/img/avatar.jpg') }}" alt="Img">
                            </div>
                        </div>
                        <div class="text-center">
                            <div class="badge badge-md mb-3" :class="agentStatusBadgeClass">@{{ agentStatusText }}</div>
                            <h6 v-if="agentStatusText !== 'Absent'" class="fw-medium d-flex align-items-center justify-content-center mb-3">
                                <i class="ti ti-fingerprint text-primary me-1"></i>
                                Arrive a @{{ arrivedAtText }}
                            </h6>
                        </div>
                        <div class="border-top pt-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-gray-5">Station affectee</span>
                                <span class="fw-bold text-info">@{{ (agent.station && agent.station.name) ? agent.station.name : '---' }}</span>
                            </div>
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
                                    <p class="fw-medium text-truncate">Total heures (journalier)</p>
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
                                    <p class="fw-medium text-truncate">Presences (mensuel)</p>
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
                                    <p class="fw-medium text-truncate">Retards (mensuel)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-xl-3">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-dark-transparent me-1"></i>Horaire affecte</p>
                                            <h3>@{{ schedule ? schedule.name : '---' }}</h3>
                                        </div>
                                    </div>
                                    <div class="col-xl-3">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-success me-1"></i>Heure debut</p>
                                            <h3>@{{ (schedule && schedule.expected_start) ? schedule.expected_start.replace(':','h') : '--:--' }}</h3>
                                        </div>
                                    </div>
                                    <div class="col-xl-3">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-info me-1"></i>Controle intermediaire</p>
                                            <h3>@{{ (schedule && schedule.expected_mid_check) ? schedule.expected_mid_check.replace(':','h') : '--:--' }}</h3>
                                        </div>
                                    </div>
                                    <div class="col-xl-3">
                                        <div class="mb-3">
                                            <p class="d-flex align-items-center mb-1"><i class="ti ti-point-filled text-warning me-1"></i>Heure fin</p>
                                            <h3>@{{ (schedule && schedule.expected_end) ? schedule.expected_end.replace(':','h') : '--:--' }}</h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between flex-wrap row-gap-2">
                                    <span
                                        v-for="(t, idx) in timeSlots"
                                        :key="idx"
                                        class="fs-10"
                                        :class="{
                                            'text-success fw-semibold': idx === highlightedTimeIndices.startIdx || idx === highlightedTimeIndices.endIdx,
                                            'text-info fw-semibold': idx === highlightedTimeIndices.midIdx
                                        }"
                                    >
                                        @{{ t }}
                                    </span>
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

                            <div class="flex-fill me-3" style="width: 260px;">
                                <select class="form-select" v-model="filters.station_id" ref="stationSelect">
                                    <option value="">Toutes les stations</option>
                                    <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                                </select>
                            </div>

                            <div class="me-3">
                                <div class="input-icon position-relative">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar text-gray-9"></i>
                                    </span>
                                    <input type="text" class="form-control date-range bookingrange" placeholder="dd/mm/yyyy - dd/mm/yyyy">
                                </div>
                            </div>

                            <div class="dropdown me-3" v-if="activeTab === 'presences'">
                                <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                    Statut
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end p-3">
                                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1" @click="filters.status = ''">Tous</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1" @click="filters.status = 'present'">Present</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1" @click="filters.status = 'absent'">Absent</a></li>
                                    <li><a href="javascript:void(0);" class="dropdown-item rounded-1" @click="filters.status = 'late'">Retard</a></li>
                                </ul>
                            </div>

                            <button class="btn btn-info-light" @click="refreshAll">Actualiser</button>
                            @can('agents.export')
                                <div class="dropdown ms-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        Portee: @{{ exportScopeLabel }}
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end p-3">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" @click.prevent="exportScope = 'filtered'">Filtres actifs</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" @click.prevent="exportScope = 'global'">Globale</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="dropdown ms-2">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                                        <i class="ti ti-file-export me-1"></i>Exporter
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end p-3">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" @click.prevent="openExport('excel')">Exporter en Excel</a>
                                        </li>
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1" @click.prevent="openExport('pdf')">Exporter en PDF</a>
                                        </li>
                                    </ul>
                                </div>
                                <span class="badge badge-info-transparent ms-2">Jeu: @{{ exportDatasetLabel }}</span>
                            @endcan
                            <span class="text-muted ms-2" v-if="isLoading">Chargement...</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-tabs-solid mb-3">
                            <li class="nav-item">
                                <a href="javascript:void(0);" class="nav-link" :class="{ active: activeTab === 'presences' }" @click="switchTab('presences')">Presences</a>
                            </li>
                            <li class="nav-item">
                                <a href="javascript:void(0);" class="nav-link" :class="{ active: activeTab === 'maintenances' }" @click="switchTab('maintenances')">Maintenances</a>
                            </li>
                        </ul>

                        <div class="table-responsive" v-show="activeTab === 'presences'">
                            <table class="table" ref="tablePresences">
                                <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Station affectation</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Heure entree</th>
                                    <th>Controle intermediaire</th>
                                    <th>Heure sortie</th>
                                    <th>Retard</th>
                                    <th>Total heures</th>
                                    <th>Photo debut</th>
                                    <th>Photo fin</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="p in filteredRows" :key="'p-' + p.id">
                                    <td>@{{ p.date_reference_iso || p.date_reference }}</td>
                                    <td>@{{ (p.assigned_station && p.assigned_station.name) ? p.assigned_station.name : '-' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm me-2">
                                                <img v-if="p.photos_debut" :src="p.photos_debut" :data-zoom="p.photos_debut" class="rounded-circle" alt="check-in">
                                                <img v-else src="{{ asset('assets/img/avatar.jpg') }}" class="rounded-circle" alt="check-in">
                                            </span>
                                            <span>@{{ (p.station_check_in && p.station_check_in.name) ? p.station_check_in.name : '-' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm me-2">
                                                <img v-if="p.photos_fin" :src="p.photos_fin" :data-zoom="p.photos_fin" class="rounded-circle" alt="check-out">
                                                <img v-else src="{{ asset('assets/img/avatar.jpg') }}" class="rounded-circle" alt="check-out">
                                            </span>
                                            <span>@{{ (p.station_check_out && p.station_check_out.name) ? p.station_check_out.name : '-' }}</span>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-success-transparent">@{{ p.started_at || '--:--' }}</span></td>
                                    <td><span class="badge badge-info-transparent">@{{ p.mid_check || '--:--' }}</span></td>
                                    <td><span class="badge badge-danger-transparent">@{{ p.ended_at || '--:--' }}</span></td>
                                    <td>
                                        <span class="badge badge-warning d-inline-flex align-items-center" v-if="p.retard === 'oui'">
                                            <i class="ti ti-clock-hour-11 me-1"></i>Oui
                                        </span>
                                        <span class="badge badge-success d-inline-flex align-items-center" v-else>
                                            <i class="ti ti-clock-hour-11 me-1"></i>Non
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info d-inline-flex align-items-center">
                                            <i class="ti ti-clock-hour-11 me-1"></i>@{{ p.duree || '--' }}
                                        </span>
                                    </td>
                                    <td>
                                        <img v-if="p.photos_debut" :src="p.photos_debut" :data-zoom="p.photos_debut" class="rounded border" style="width:42px;height:42px;object-fit:cover;" alt="photo debut">
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                        <img v-if="p.photos_fin" :src="p.photos_fin" :data-zoom="p.photos_fin" class="rounded border" style="width:42px;height:42px;object-fit:cover;" alt="photo fin">
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-responsive" v-show="activeTab === 'maintenances'">
                            <table class="table" ref="tableMaintenances">
                                <thead class="thead-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Station</th>
                                    <th>Heure debut</th>
                                    <th>Heure fin</th>
                                    <th>Distance</th>
                                    <th>Photo debut</th>
                                    <th>Photo fin</th>
                                    <th>Statut</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="m in maintenanceRows" :key="'m-' + m.id">
                                    <td>@{{ m.date_maintenance_iso || m.date_maintenance }}</td>
                                    <td>@{{ m.station?.name || '-' }}</td>
                                    <td><span class="badge badge-soft-success">@{{ m.started_at || '--:--' }}</span></td>
                                    <td><span class="badge badge-soft-dark">@{{ m.end_at || '--:--' }}</span></td>
                                    <td>@{{ m.distance_label || 'Distance indisponible' }}</td>
                                    <td>
                                        <img v-if="m.photo_debut" :src="m.photo_debut" :data-zoom="m.photo_debut" class="rounded border" style="width:42px;height:42px;object-fit:cover;" alt="photo debut maintenance">
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                        <img v-if="m.photo_fin" :src="m.photo_fin" :data-zoom="m.photo_fin" class="rounded border" style="width:42px;height:42px;object-fit:cover;" alt="photo fin maintenance">
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                        <span v-if="m.end_at" class="badge badge-success-transparent">Cloturee</span>
                                        <span v-else class="badge badge-warning-transparent">En cours</span>
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
    <script>
        window.__SITES__ = @json($sites ?? []);
    </script>
    <script type="module" src="{{ asset("assets/js/scripts/agent-attendance.js") . '?v=' . filemtime(public_path('assets/js/scripts/agent-attendance.js')) }}"></script>
@endpush
