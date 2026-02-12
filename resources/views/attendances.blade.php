@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center page-breadcrumb justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Pointages Globaux</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active">Historique des Présences</li>
                    </ol>
                </nav>
            </div>

            <div class="dropdown">
                <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-file-export me-1"></i>Exporter
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-3" style="">
                    <li>
                        <a class="dropdown-item rounded-1" :href="exportExcelUrl" target="_blank">Exporter en Excel</a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-1" :href="exportPdfUrl" target="_blank">Exporter en PDF</a>
                    </li>
                </ul>
            </div>

        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Journal de Pointage</h5>
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-fill" style="width: 260px;">
                        <select class="form-select mb-2" v-model="filters.station_id" ref="stationSelect">
                            <option value="">Toutes les stations</option>
                            <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                        </select>
                    </div>
                    <div class="me-2">
                        <input type="date" class="form-control" v-model="filters.date">
                    </div>
                    <div class="">
                        <button class="btn btn-outline-info border d-inline-flex align-items-center" @click="load">
                            <i class="ti ti-refresh me-1"></i>Actualiser
                        </button>
                    </div>
                    <span class="text-muted" v-if="isLoading">Chargement...</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" ref="table">
                        <thead class="thead-light">
                        <tr>
                            <th>Agent</th>
                            <th>Station affectation</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Date</th>
                            <th>Heure entrée</th>
                            <th>Heure sortie</th>
                            <th>Durée</th>
                            <th>Retard</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="p in presences" :key="p.id">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" class="rounded-circle">
                                    </span>
                                    <div>
                                        <h6 class="mb-0">@{{ p.agent?.fullname ?? '-' }}</h6>
                                        <small>@{{ p.agent?.matricule ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-success">@{{ p.assigned_station?.name ?? '-' }}</span></td>
                            <td><span class="badge badge-soft-info">@{{ p.station_check_in?.name ?? '-' }}</span></td>
                            <td><span class="badge badge-soft-dark">@{{ p.station_check_out?.name ?? '-' }}</span></td>
                            <td>@{{ p.date_reference }}</td>
                            <td><span class="badge badge-success">@{{ p.started_at ?? '--:--' }}</span></td>
                            <td><span class="badge badge-purple">@{{ p.ended_at ?? '--:--' }}</span></td>
                            <td><span class="badge badge-info">@{{ p.duree ?? '--' }}</span></td>
                            <td>
                                <span class="badge badge-soft-danger" v-if="p.retard === 'oui'">Oui</span>
                                <span class="badge badge-soft-success" v-else>Non</span>
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
    <script type="module" src="{{ asset("assets/js/scripts/attendances.js") }}"></script>
@endpush
