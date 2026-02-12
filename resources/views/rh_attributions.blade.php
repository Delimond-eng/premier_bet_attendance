@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Attribution congé à un agent</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Ressources humaines</li>
                        <li class="breadcrumb-item active" aria-current="page">Attribution agent</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#attribution_modal" @click="reset">
                    <i class="ti ti-circle-plus me-2"></i>Nouvelle assignation
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Liste des assignations</h5>
                <span class="text-muted" v-if="isLoading">Chargement...</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" ref="table">
                        <thead class="thead-light">
                        <tr>
                            <th>Agent</th>
                            <th>Station</th>
                            <th>Type</th>
                            <th>Du</th>
                            <th>Au</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="c in rows" :key="c.id">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img :src="c.agent?.photo || defaultAvatar" class="rounded-circle" alt="img">
                                    </span>
                                    <div>
                                        <h6 class="mb-0">@{{ c.agent?.fullname ?? '-' }}</h6>
                                        <small class="text-muted">@{{ c.agent?.matricule ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>@{{ c.agent?.station?.name ?? '-' }}</td>
                            <td>@{{ c.conge_type?.libelle ?? c.type ?? '-' }}</td>
                            <td>@{{ c.date_debut_label ?? c.date_debut }}</td>
                            <td>@{{ c.date_fin_label ?? c.date_fin }}</td>
                            <td>
                                <span class="badge" :class="statusClass(c.status)">@{{ statusLabel(c.status) }}</span>
                            </td>
                            <td class="text-end">
                                <a href="javascript:void(0);" class="me-2 text-info" @click="edit(c)"><i class="ti ti-edit"></i></a>
                                <a href="javascript:void(0);" class="text-danger" @click="remove(c)"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="attribution_modal" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">@{{ form.id ? 'Modification assignation' : 'Nouvelle assignation' }}</h4>
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
                                        <select class="form-select" v-model="form.agent_id" required>
                                            <option value="" hidden>--Sélectionner agent--</option>
                                            <option v-for="a in agents" :key="a.id" :value="a.id">@{{ a.fullname }} (@{{ a.matricule }})</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Type de congé<span class="text-danger"> *</span></label>
                                        <select class="form-select" v-model="form.conge_type_id" required>
                                            <option value="" hidden>--Sélectionner type--</option>
                                            <option v-for="t in types" :key="t.id" :value="t.id">@{{ t.libelle }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date début<span class="text-danger"> *</span></label>
                                        <input type="date" class="form-control" v-model="form.date_debut" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Date fin<span class="text-danger"> *</span></label>
                                        <input type="date" class="form-control" v-model="form.date_fin" required>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Motif</label>
                                        <textarea class="form-control" v-model="form.motif"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-select" v-model="form.status">
                                            <option value="pending">En attente</option>
                                            <option value="approved">Approuvé</option>
                                            <option value="rejected">Rejeté</option>
                                        </select>
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
    <script type="module" src="{{ asset("assets/js/scripts/rh-attributions.js") }}"></script>
@endpush

