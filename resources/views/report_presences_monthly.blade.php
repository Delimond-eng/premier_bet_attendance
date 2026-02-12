@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Rapport des présences (mensuel)</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Rapports</li>
                        <li class="breadcrumb-item active" aria-current="page">Mensuel</li>
                    </ol>
                </nav>
            </div>

            <a class="btn btn-white border" :href="pdfUrl" target="_blank">
                <i class="ti ti-file-type-pdf me-1"></i>PDF
            </a>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5>Synthèse agents</h5>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select" v-model.number="filters.month" style="max-width: 200px;">
                        <option v-for="m in monthOptions" :key="m.value" :value="m.value">@{{ m.label }}</option>
                    </select>
                    <select class="form-select" v-model.number="filters.year" style="max-width: 140px;">
                        <option v-for="y in yearOptions" :key="y" :value="y">@{{ y }}</option>
                    </select>
                    <select class="form-select" v-model="filters.station_id" style="max-width: 260px;">
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
                            <th>Agent</th>
                            <th>Présent</th>
                            <th>Retard</th>
                            <th>Absent</th>
                            <th>Congé</th>
                            <th>Autorisation</th>
                            <th>Justif retard</th>
                            <th>Justif absence</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="r in rows" :key="r.agent_key">
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
                            <td>
                                @{{ r.present }}

                            </td>
                            <td>@{{ r.retard }}</td>
                            <td>@{{ r.absent }}</td>
                            <td>@{{ r.conge }}</td>
                            <td>@{{ r.autorisation }}</td>
                            <td>@{{ r.retard_justifie }}</td>
                            <td>@{{ r.absence_justifiee }}</td>
                            <td><span class="badge badge-info ms-2">Total presté : @{{ r.total_preste }}</span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/report-presences-monthly.js") }}"></script>
@endpush
