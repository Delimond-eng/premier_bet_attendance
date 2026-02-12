@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Congés (types)</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Ressources humaines</li>
                        <li class="breadcrumb-item active" aria-current="page">Congés</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#conge_type_modal" @click="reset">
                    <i class="ti ti-circle-plus me-2"></i>Ajouter type
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Liste des types de congés</h5>
                <span class="text-muted" v-if="isLoading">Chargement...</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" ref="table">
                        <thead class="thead-light">
                        <tr>
                            <th>Libellé</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="t in types" :key="t.id">
                            <td class="fw-medium">@{{ t.libelle }}</td>
                            <td>@{{ t.description || '-' }}</td>
                            <td>
                                <span class="badge" :class="typeStatusClass(t.status)">@{{ typeStatusLabel(t.status) }}</span>
                            </td>
                            <td class="text-end">
                                <a href="javascript:void(0);" class="me-2 text-info" @click="edit(t)"><i class="ti ti-edit"></i></a>
                                <a href="javascript:void(0);" class="text-danger" @click="remove(t)"><i class="ti ti-trash"></i></a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="conge_type_modal" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">@{{ form.id ? 'Modification type' : 'Création type' }}</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="save">
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Libellé<span class="text-danger"> *</span></label>
                                        <input class="form-control" v-model="form.libelle" placeholder="ex: Annuel" required>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" v-model="form.description"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-select" v-model="form.status">
                                            <option value="actif">Actif</option>
                                            <option value="inactif">Inactif</option>
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
    <script type="module" src="{{ asset("assets/js/scripts/rh-conges.js") }}"></script>
@endpush

