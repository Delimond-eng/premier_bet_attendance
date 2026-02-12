@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>

        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Gestions des stations</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            Admin
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Gestion des stations</li>
                    </ol>
                </nav>
            </div>

            <a href="#" data-bs-target="#add_station" data-bs-toggle="modal" class="btn btn-primary-gradient">
                <i class="ti ti-plus"></i> Nouvelle station
            </a>
        </div>
        <!-- /Breadcrumb -->

        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="mb-0">Liste des stations</h5>
                        <div class="d-flex align-items-center gap-2">
                            <input type="date" class="form-control" v-model="filters.date" style="max-width: 180px;">
                            <button class="btn btn-white border" @click="load">Actualiser</button>
                            <div class="dropdown">
                                <a href="javascript:void(0);" class="dropdown-toggle btn btn-light rounded-pill text-dark dropdown-icon-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-qrcode fs-16"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end p-3">
                                    <li>
                                        <a href="{{ route('stations.qrcode') }}" class="dropdown-item rounded-1">Télécharger les qrcodes</a>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive candidates-table">
                            <table class="table table-nowrap mb-0" ref="table">
                                <thead class="bg-light-gray">
                                <tr>
                                    <th class="fw-bold bg-white px-2 ps-0">Station</th>
                                    <th class="fw-normal bg-white px-2">Agents affectés</th>
                                    <th class="fw-normal bg-white px-2">Agents présents</th>
                                    <th class="fw-normal bg-white px-2">Agents absents</th>
                                    <th class="fw-normal bg-white px-2">Retard</th>
                                    <th class="fw-normal bg-white px-2"></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="s in sites" :key="s.id">
                                    <td class="px-0 pe-5">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <h6 class="fw-normal mb-1 fs-14"><a href="javascript:void(0);">@{{ s.name }}</a></h6>
                                                <span class="fs-13 d-inline-flex align-items-center">@{{ s.adresse ?? '' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="badge bg-info fs-13 rounded-xxl py-2">@{{ s.agents_count ?? 0 }}</div>
                                    </td>
                                    <td>
                                        <div class="badge bg-success fs-13 rounded-xxl py-2">@{{ s.presences_count ?? 0 }}</div>
                                    </td>
                                    <td>
                                        <div class="badge bg-danger fs-13 rounded-xxl text-light py-2">@{{ Math.max((s.agents_count ?? 0) - (s.presences_count ?? 0), 0) }}</div>
                                    </td>
                                    <td>
                                        <div class="badge bg-warning fs-13 rounded-xxl py-2">@{{ s.late_count ?? 0 }}</div>
                                    </td>
                                    <td>
                                        <div class="action-icon d-inline-flex">
                                            <a href="javascript:void(0);" class="me-2 text-info" @click="edit(s)"><i class="ti ti-edit"></i></a>
                                            <a href="javascript:void(0);" class="me-2 text-danger" @click="remove(s)"><i class="ti ti-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="add_station" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">@{{ form.id ? 'Modification station' : 'Création station' }}</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="save">
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Nom de la station<span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" v-model="form.name" placeholder="ex: Direction Générale">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Code<span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" v-model="form.code" placeholder="ex: DG001">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Adresse<span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" v-model="form.adresse" placeholder="Adresse...">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Coordonnées GPS (lat,lng)<span class="text-danger"> (facultatif)</span></label>
                                        <input type="text" class="form-control" v-model="form.latlng" placeholder="-4.321,15.312">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Téléphone<span class="text-danger"> (facultatif)</span></label>
                                        <input type="text" class="form-control" v-model="form.phone" placeholder="+243...">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre d'agents attendus<span class="text-danger"> (facultatif)</span></label>
                                        <input type="number" class="form-control" v-model="form.presence" placeholder="0">
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
    <script type="module" src="{{ asset("assets/js/scripts/stations.js") }}"></script>
@endpush

