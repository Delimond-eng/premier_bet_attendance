@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Rapport des alertes cumulees</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Rapports</li>
                        <li class="breadcrumb-item active" aria-current="page">Alertes cumulees</li>
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
                <h5>Periode: @{{ range.from }} -> @{{ range.to }}</h5>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select" v-model="filters.period" style="max-width: 200px;">
                        <option value="daily">Journaliere</option>
                        <option value="weekly">Hebdo</option>
                        <option value="monthly">Mensuelle</option>
                    </select>
                    <input type="date" class="form-control" v-model="filters.from" style="max-width: 140px;">
                    <input type="date" class="form-control" v-model="filters.to" style="max-width: 140px;">
                    <input type="number" min="1" max="31" class="form-control" v-model.number="filters.threshold" style="max-width: 140px;" placeholder="Seuil">
                    <div class="flex-fill" style="width: 320px;">
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
                <ul class="nav nav-tabs nav-tabs-solid mb-3">
                    <li class="nav-item">
                        <a href="javascript:void(0);" class="nav-link" :class="{ active: activeTab === 'absences' }" @click="switchTab('absences')">
                            Alerte des absences
                            <span class="badge badge-danger fs-10 fw-medium text-white p-1 ms-1">@{{ counts.absences }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="javascript:void(0);" class="nav-link" :class="{ active: activeTab === 'retards' }" @click="switchTab('retards')">
                            Alerte des retards
                            <span class="badge badge-danger fs-10 fw-medium text-white p-1 ms-1">@{{ counts.retards }}</span>
                        </a>
                    </li>
                </ul>

                <div class="table-responsive" v-show="activeTab === 'absences'">
                    <table class="table table-bordered table-sm align-middle" ref="tableAbsences">
                        <thead class="thead-light">
                        <tr>
                            <th>Mois</th>
                            <th>Agent</th>
                            <th>Station</th>
                            <th>Groupe</th>
                            <th>Cumul absences</th>
                            <th>Seuil</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="r in absencesRows" :key="'a-' + r.key">
                            <td>@{{ r.month_label }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img v-if="r.agent?.photo" :src="r.agent?.photo" :data-zoom="r.agent?.photo" class="rounded-circle" alt="img">
                                        <img v-else src="{{asset("assets/img/avatar.jpg")}}" class="rounded-circle" alt="img">
                                    </span>
                                    <div>
                                        <h6 class="mb-0">@{{ r.agent?.fullname ?? '-' }}</h6>
                                        <small class="text-muted">@{{ r.agent?.matricule ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-lg badge-purple">@{{ r.agent?.station_name ?? '-' }}</span></td>
                            <td>@{{ r.agent?.group_name ?? '-' }}</td>
                            <td><span class="badge badge-danger fs-10 fw-medium text-white p-1">@{{ r.count }}</span></td>
                            <td>@{{ r.threshold }}</td>
                            <td><span class="badge badge-warning text-dark">@{{ r.action_label }}</span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive" v-show="activeTab === 'retards'">
                    <table class="table table-bordered table-sm align-middle" ref="tableRetards">
                        <thead class="thead-light">
                        <tr>
                            <th>Mois</th>
                            <th>Agent</th>
                            <th>Station</th>
                            <th>Groupe</th>
                            <th>Cumul retards</th>
                            <th>Seuil</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="r in retardsRows" :key="'r-' + r.key">
                            <td>@{{ r.month_label }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img v-if="r.agent?.photo" :src="r.agent?.photo" :data-zoom="r.agent?.photo" class="rounded-circle" alt="img">
                                        <img v-else src="{{asset("assets/img/avatar.jpg")}}" class="rounded-circle" alt="img">
                                    </span>
                                    <div>
                                        <h6 class="mb-0">@{{ r.agent?.fullname ?? '-' }}</h6>
                                        <small class="text-muted">@{{ r.agent?.matricule ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-lg badge-purple">@{{ r.agent?.station_name ?? '-' }}</span></td>
                            <td>@{{ r.agent?.group_name ?? '-' }}</td>
                            <td><span class="badge badge-danger fs-10 fw-medium text-white p-1">@{{ r.count }}</span></td>
                            <td>@{{ r.threshold }}</td>
                            <td><span class="badge badge-warning text-dark">@{{ r.action_label }}</span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/report-alerts-cumulative.js") }}"></script>
@endpush
