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
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5>Synthèse par station</h5>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" min="1" max="12" class="form-control" v-model="filters.month" style="max-width: 100px;">
                    <input type="number" min="2020" class="form-control" v-model="filters.year" style="max-width: 100px;">
                    <select class="form-select" v-model="filters.station_id" style="max-width:300px;">
                        <option value="">Toutes les stations</option>
                        <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                    </select>
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
                            <td>@{{ r.station }}</td>
                            <td>@{{ r.agents }}</td>
                            <td>@{{ r.present }}</td>
                            <td>@{{ r.retard }}</td>
                            <td>@{{ r.absent }}</td>
                            <td>@{{ r.conge }}</td>
                            <td>@{{ r.autorisation }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-white border" :href="stationReportUrl(r)">
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

