@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Pointages Globaux</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item active">Historique des Présences</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center flex-wrap gap-2">
                <select class="form-select mb-2" v-model="filters.station_id" style="max-width: 260px;">
                    <option value="">Toutes les stations</option>
                    <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                </select>
                <div class="me-2 mb-2">
                    <input type="date" class="form-control" v-model="filters.date">
                </div>
                <div class="mb-2">
                    <button class="btn btn-white border d-inline-flex align-items-center" @click="load">
                        <i class="ti ti-refresh me-1"></i>Actualiser
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Journal de Pointage</h5>
                <div class="text-muted" v-if="isLoading">Chargement...</div>
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
                            <td>@{{ p.assigned_station?.name ?? '-' }}</td>
                            <td>@{{ p.station_check_in?.name ?? '-' }}</td>
                            <td>@{{ p.station_check_out?.name ?? '-' }}</td>
                            <td>@{{ p.date_reference }}</td>
                            <td class="fw-bold text-success">@{{ p.started_at ?? '--:--' }}</td>
                            <td class="fw-bold text-danger">@{{ p.ended_at ?? '--:--' }}</td>
                            <td>@{{ p.duree ?? '--' }}</td>
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
