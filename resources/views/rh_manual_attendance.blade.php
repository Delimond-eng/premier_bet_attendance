@extends('layouts.app')

@section('content')
    <div class="content" id="ManualAttendanceApp" v-cloak>
        <div class="page-header mb-3">
            <div>
                <h2 class="mb-1">Pointage manuel</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/">Accueil</a></li>
                        <li class="breadcrumb-item">Administration RH</li>
                        <li class="breadcrumb-item active">Pointage manuel</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label" for="manual-agent">Agent</label>
                        <select id="manual-agent" class="form-select" ref="agentSelect">
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="manual-month">Mois</label>
                        <input id="manual-month" type="month" class="form-control" v-model="month" @change="loadMonth" :disabled="!agentId">
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted small" v-if="agent">@{{ agent.fullname }} — @{{ agent.matricule }}</div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div v-if="error" class="alert alert-danger" role="alert">@{{ error }}</div>
                <div v-if="success" class="alert alert-success" role="status">@{{ success }}</div>
                <div v-if="isLoading" class="text-center py-5">
                    <span class="spinner-border spinner-border-sm me-2"></span>Chargement du mois...
                </div>
                <div v-else-if="!agentId" class="text-center text-muted py-5">
                    Sélectionnez d’abord un agent pour afficher son calendrier mensuel.
                </div>
                <div v-else-if="days.length === 0" class="text-center text-muted py-5">
                    Aucun jour à afficher pour ce mois.
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Jour</th>
                                <th>Horaire appliqué</th>
                                <th>Entrée</th>
                                <th>Sortie</th>
                                <th>État</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="day in days" :key="day.date" :class="day.completed ? 'table-success' : ''">
                                <td class="text-nowrap">
                                    <strong>@{{ formatDate(day.date) }}</strong>
                                    <span class="text-muted text-capitalize ms-1">@{{ day.weekday }}</span>
                                </td>
                                <td>@{{ day.horaire }}</td>
                                <td>
                                    <input type="time" class="form-control" v-model="day.input_start"
                                        :disabled="day.future || day.completed || day.has_entry">
                                </td>
                                <td>
                                    <input type="time" class="form-control" v-model="day.input_end"
                                        :disabled="day.future || day.completed">
                                </td>
                                <td>
                                    <span v-if="day.future" class="badge bg-secondary">À venir</span>
                                    <span v-else-if="day.completed" class="badge bg-success">Pointage effectué</span>
                                    <span v-else-if="day.has_entry" class="badge bg-warning text-dark">Entrée enregistrée — sortie à compléter</span>
                                    <span v-else class="badge bg-light text-dark">À pointer</span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-primary" type="button"
                                        @click="save(day)" :disabled="day.future || day.completed || day.saving || !canSave(day)">
                                        <span v-if="day.saving" class="spinner-border spinner-border-sm me-1"></span>
                                        @{{ day.has_entry ? 'Enregistrer la sortie' : 'Pointer' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="days.length" class="small text-muted mb-0">
                    Les journées futures et les pointages déjà complets sont verrouillés. Une journée avec une entrée existante permet uniquement de compléter la sortie.
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        window.manualAttendanceDefaultMonth = @json($defaultMonth);
    </script>
    <script type="module" src="{{ asset('assets/js/scripts/rh-manual-attendance.js') . '?v=' . filemtime(public_path('assets/js/scripts/rh-manual-attendance.js')) }}"></script>
@endpush
