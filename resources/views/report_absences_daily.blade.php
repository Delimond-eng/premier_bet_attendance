@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Rapport des absences (journalier)</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Rapports</li>
                        <li class="breadcrumb-item active" aria-current="page">Absences</li>
                    </ol>
                </nav>
            </div>

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

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5>Liste des absences (@{{ range.from }} → @{{ range.to }})</h5>
                <div class="d-flex align-items-center gap-2">
                    <input type="date" class="form-control" v-model="filters.from" style="max-width: 180px;">
                    <input type="date" class="form-control" v-model="filters.to" style="max-width: 180px;">
                    <div class="flex-fill" style="width: 260px;">
                        <select class="form-select" v-model="filters.station_id" ref="stationSelect">
                            <option value="">Toutes les stations</option>
                            <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" @click="load" :disabled="isLoading">
                        @{{ isLoading ? 'Chargement...' : 'Charger' }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" ref="table">
                        <thead class="thead-light">
                        <tr>
                            <th>Date</th>
                            <th>Agent</th>
                            <th>Station affectée</th>
                            <th>Groupe</th>
                            <th>Horaire</th>
                            <th>Heure attendue</th>
                            <th>Justificatif</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="r in rows" :key="r.key">
                            <td>@{{ r.date }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img :src="r.agent?.photo || 'https://smarthr.co.in/demo/html/template/assets/img/users/user-26.jpg'" class="rounded-circle" alt="img">
                                    </span>
                                    <div>
                                        <h6 class="mb-0">@{{ r.agent?.fullname ?? '-' }}</h6>
                                        <small class="text-muted">@{{ r.agent?.matricule ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-lg badge-purple">@{{ r.agent?.station_name ?? '-' }}</span></td>
                            <td class="text-dark fw-bold">@{{ r.agent?.group_name ?? '-' }}</td>
                            <td><span class="badge badge-info-transparent">@{{ r.agent?.schedule_label ?? '-' }}</span></td>
                            <td><span class="badge badge-soft-warning">@{{ r.agent?.expected_time ?? '--:--' }}</span></td>
                            <td>
                                <span class="badge" :class="r.justificatif !=='aucun' ? 'badge-info' : 'badge-danger'">@{{ r.justificatif ?? 'aucun' }}</span>
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
    <script type="module" src="{{ asset("assets/js/scripts/report-absences-daily.js") }}"></script>
@endpush
