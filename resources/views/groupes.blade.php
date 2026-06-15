@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>

        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Gestion des groupes agent</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            Admin
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Gestion groupes.</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">

                @can('groupes.create')
                <div class="mb-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_group" class="btn btn-primary d-flex align-items-center">
                        <i class="ti ti-circle-plus me-2"></i>Ajout groupe
                    </a>
                </div>
                @endcan

            </div>
        </div>
        <!-- /Breadcrumb -->

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Liste des groupes des agents</h5>
            </div>
            <div class="card-body">
                <div v-if="groupedGroups.length === 0" class="text-muted">Aucune donnée.</div>

                <div v-for="groupe in groupedGroups" :key="groupe.key" class="mb-4">
                    <div class="alert alert-info d-flex align-items-center mb-2 py-2">
                        <i class="ti ti-home-bolt me-2"></i>
                        <strong>Station : @{{ groupe.station_name }}</strong>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead class="thead-light">
                            <tr>
                                <th class="no-sort">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </th>
                                <th>Designation</th>
                                <th>Horaire</th>
                                <th>Statut</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="g in groupe.rows" :key="g.id">
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><h6 class="fs-14 fw-medium">@{{ g.libelle }}</h6></td>
                                <td>
                                    <div v-if="g.horaire">
                                        <h6 class="fs-14 fw-medium mb-0">@{{ g.horaire.libelle }}</h6>
                                        <small>De @{{ g.horaire.started_at }} à @{{ g.horaire.ended_at }}</small>
                                    </div>
                                    <div v-else>--Permutable--</div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center badge-xs" v-if="g.status === 'actif'">
                                        <i class="ti ti-point-filled me-1"></i>Actif
                                    </span>
                                    <span class="badge badge-soft-danger d-inline-flex align-items-center badge-xs" v-else>
                                        <i class="ti ti-point-filled me-1"></i>Inactif
                                    </span>
                                </td>
                                <td>
                                    <div class="action-icon d-inline-flex">
                                        @can('groupes.update')
                                        <a href="javascript:void(0);" class="me-2 text-info" @click="edit(g)"><i class="ti ti-edit"></i></a>
                                        @endcan
                                        @can('groupes.delete')
                                        <a href="javascript:void(0);" class="text-danger" @click="remove(g)"><i class="ti ti-trash"></i></a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @canany(['groupes.create', 'groupes.update'])
        <div class="modal fade" id="add_group" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">@{{ form.id ? 'Modification groupe agent' : 'Création groupe agent' }}</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close" @click="reset">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="save">
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Désignation<span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" v-model="form.libelle" placeholder="ex: Matinale">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Station<span class="text-danger"> *</span></label>
                                        <select class="form-select" v-model="form.station_id" :required="form.horaire_id !== ''">
                                            <option value="" hidden>--Sélectionner station--</option>
                                            <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                                        </select>
                                        <small class="text-muted">Choisis la station pour filtrer les horaires.</small>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Horaire<span class="text-danger"> *</span></label>
                                        <select class="form-select" v-model="form.horaire_id">
                                            <option value="" hidden>--Sélectionner horaire--</option>
                                            <option v-for="h in filteredHoraires" :key="h.id" :value="h.id">
                                                @{{ h.libelle }} (@{{ h.started_at }}-@{{ h.ended_at }}) - @{{ stationName(h.site_id) }}
                                            </option>
                                        </select>
                                        <small class="text-muted">Les horaires affichés correspondent à la station choisie.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal" @click="reset">Annuler</button>
                            <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                @{{ isLoading ? "Enregistrement..." : "Enregistrer" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcanany

    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/groupes.js") }}"></script>
@endpush
