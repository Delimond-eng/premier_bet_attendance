@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>

        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Gestion des horaires de présence</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            Admin
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Gestion horaires.</li>
                    </ol>
                </nav>
            </div>

            <a href="#" data-bs-toggle="modal" data-bs-target="#add_horaire" class="btn btn-primary d-flex align-items-center">
                <i class="ti ti-circle-plus me-2"></i>Ajout horaire
            </a>
        </div>
        <!-- /Breadcrumb -->

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Liste des horaires</h5>
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select" v-model="filters.site_id" style="max-width: 260px;">
                        <option value="">Toutes les stations</option>
                        <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                    </select>
                    <button class="btn btn-white border" @click="load">Filtrer</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" ref="table">
                        <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Designation</th>
                            <th>Station</th>
                            <th>Heure début</th>
                            <th>Heure fin</th>
                            <th>Tolérance</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="h in horaires" :key="h.id">
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td><h6 class="fs-14 fw-medium">@{{ h.libelle }}</h6></td>
                            <td>@{{ stationName(h.site_id) }}</td>
                            <td><span class="badge badge-info">@{{ h.started_at }}</span></td>
                            <td><span class="badge badge-dark">@{{ h.ended_at }}</span></td>
                            <td><span class="badge badge-purple">@{{ h.tolerence_minutes }} min</span></td>

                            <td>
                                <div class="action-icon d-inline-flex">
                                    <a href="javascript:void(0);" class="me-2 text-info" @click="edit(h)"><i class="ti ti-edit"></i></a>
                                    <a href="javascript:void(0);" class="text-danger" @click="remove(h)"><i class="ti ti-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="add_horaire" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">@{{ form.id ? 'Modification horaire' : 'Création horaire' }}</h4>
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
                                        <select class="form-select" v-model="form.site_id">
                                            <option value="" hidden>--Sélectionner station--</option>
                                            <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Heure début<span class="text-danger"> *</span></label>
                                        <input type="time" class="form-control" v-model="form.started_at">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Heure Fin<span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" v-model="form.ended_at">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Tolérance (minutes)</label>
                                        <input type="number" class="form-control" v-model="form.tolerence_minutes" min="0" placeholder="15">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal" @click="reset">Annuler</button>
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
    <script type="module" src="{{ asset("assets/js/scripts/horaires.js") }}"></script>
@endpush
