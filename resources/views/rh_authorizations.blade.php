@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Autorisations spéciales</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Ressources humaines</li>
                        <li class="breadcrumb-item active" aria-current="page">Autorisations</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#auth_modal" @click="reset">
                    <i class="ti ti-circle-plus me-2"></i>Ajouter
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Liste des autorisations</h5>
                <span class="text-muted" v-if="isLoading">Chargement...</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" ref="table">
                        <thead class="thead-light">
                        <tr>
                            <th>Agent</th>
                            <th>Station</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Minutes</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="a in authorizations" :key="a.id">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img :src="a.agent?.photo || 'https://smarthr.co.in/demo/html/template/assets/img/users/user-26.jpg'" class="rounded-circle" alt="img">
                                    </span>
                                    <div>
                                        <h6 class="mb-0">@{{ a.agent?.fullname ?? '-' }}</h6>
                                        <small class="text-muted">@{{ a.agent?.matricule ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>@{{ a.agent?.station?.name ?? '-' }}</td>
                            <td>@{{ a.date_reference_label ?? a.date_reference }}</td>
                            <td>@{{ a.type }}</td>
                            <td>@{{ a.minutes ?? '--' }}</td>
                            <td>@{{ statusLabel(a.status) }}</td>
                            <td>
                                <a href="javascript:void(0);" class="me-2 text-info" @click="edit(a)"><i class="ti ti-edit"></i></a>
                                <a href="javascript:void(0);" class="text-danger" @click="remove(a)"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="auth_modal" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">@{{ form.id ? 'Modification autorisation' : 'Création autorisation' }}</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="save">
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Agent<span class="text-danger"> *</span></label>
                                        <select class="form-select" v-model="form.agent_id">
                                            <option value="" hidden>--Sélectionner agent--</option>
                                            <option v-for="ag in agents" :key="ag.id" :value="ag.id">@{{ ag.fullname }} (@{{ ag.matricule }})</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Date<span class="text-danger"> *</span></label>
                                        <input type="date" class="form-control" v-model="form.date_reference">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Type<span class="text-danger"> *</span></label>
                                        <input class="form-control" v-model="form.type" placeholder="retard, absence, maladie...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Minutes</label>
                                        <input type="number" min="0" class="form-control" v-model="form.minutes">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-select" v-model="form.status">
                                            <option value="pending">pending</option>
                                            <option value="approved">approved</option>
                                            <option value="rejected">rejected</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Motif</label>
                                        <textarea class="form-control" v-model="form.reason"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                @{{ isLoading ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/rh-authorizations.js") }}"></script>
@endpush
