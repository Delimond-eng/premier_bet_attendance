@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Pointage mensuel (RH)</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Ressources humaines</li>
                        <li class="breadcrumb-item active" aria-current="page">Pointage mensuel</li>
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
                <h5>Synthèse par station</h5>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select" v-model.number="filters.month" style="max-width: 160px;">
                        <option v-for="m in monthOptions" :key="m.value" :value="m.value">@{{ m.label }}</option>
                    </select>
                    <select class="form-select" v-model.number="filters.year" style="max-width: 120px;">
                        <option v-for="y in yearOptions" :key="y" :value="y">@{{ y }}</option>
                    </select>
                    <div class="flex-fill" style="width: 260px;">
                        <select class="form-select" v-model="filters.station_id" ref="stationSelect">
                            <option value="">Toutes les stations</option>
                            <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" @click="load" :disabled="isLoading">@{{ isLoading ? 'Chargement...' : 'Charger' }}</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" ref="table">
                        <thead class="thead-light">
                        <tr>
                            <th>Station</th>
                            <th>Agents</th>
                            <th>Présent</th>
                            <th>Retard</th>
                            <th>Absent</th>
                            <th>Congé</th>
                            <th>Autorisation</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="r in rows" :key="r.station_id">
                            <td><span class="badge badge-purple badge-lg">@{{ r.station }}</span></td>
                            <td>@{{ r.agents }}</td>
                            <td>@{{ r.present }}</td>
                            <td>@{{ r.retard }}</td>
                            <td>@{{ r.absent }}</td>
                            <td>@{{ r.conge }}</td>
                            <td>@{{ r.autorisation }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-info" :href="stationReportUrl(r)">
                                    <i class="ti ti-info-circle me-1"></i>Infos
                                </a>
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
    <script type="module" src="{{ asset("assets/js/scripts/rh-timesheet.js") }}"></script>
@endpush
