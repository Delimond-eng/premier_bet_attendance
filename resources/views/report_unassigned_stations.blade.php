@extends('layouts.app')

@section('content')
<div class="content" id="App" v-cloak>
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
        <div class="my-auto mb-2">
            <h2 class="mb-1">Alertes : Stations Non Affectées</h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item">Notifications</li>
                    <li class="breadcrumb-item active" aria-current="page">Stations Non Affectées</li>
                </ol>
            </nav>
        </div>


        <div class="dropdown">
            <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-file-export me-1"></i>Exporter
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-3">
                <li>
                    <a class="dropdown-item rounded-1" target="_blank">Exporter en Excel</a>
                </li>
                <li>
                    <a class="dropdown-item rounded-1" target="_blank">Exporter en PDF</a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0">Pointages effectués hors station d'affectation (Planning inclus)</h5>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group" style="width: 200px;">
                    <span class="input-group-text">Du</span>
                    <input type="date" v-model="filters.from" class="form-control">
                </div>
                <div class="input-group" style="width: 200px;">
                    <span class="input-group-text">Au</span>
                    <input type="date" v-model="filters.to" class="form-control">
                </div>
                <button type="button" @click="load" class="btn btn-primary" :disabled="isLoading">
                    <i class="ti ti-refresh me-2"></i> @{{ isLoading ? 'Chargement...' : 'Actualiser' }}
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle" ref="table">
                    <thead class="thead-light">
                        <tr>
                            <th>DATE</th>
                            <th>AGENT</th>
                            <th>STATION ATTENDUE</th>
                            <th>STATION POINTÉE</th>
                            <th>HEURE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in rows" :key="p.id">
                            <td>@{{ formatDate(p.date_reference) }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img v-if="p.agent?.photo" :src="p.agent.photo" class="rounded-circle" alt="user">
                                        <img v-else src="{{asset('assets/img/avatar.jpg')}}" class="rounded-circle" alt="user">
                                    </span>
                                    <div>
                                        <h6 class="mb-0">@{{ p.agent?.fullname }}</h6>
                                        <small class="text-muted">@{{ p.agent?.matricule }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-primary">@{{ p.expected_station ? p.expected_station.name : 'N/A' }}</span></td>
                            <td><span class="badge badge-soft-danger">@{{ p.station_check_in ? p.station_check_in.name : 'Inconnue' }}</span></td>
                            <td><span class="badge badge-soft-info">@{{ formatTime(p.started_at) }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('assets/js/scripts/report-unassigned-stations.js') }}"></script>
@endpush
