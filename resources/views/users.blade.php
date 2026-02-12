@extends("layouts.app")

@section("content")
    <div class="content" id="App">
        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Utilisateurs</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="index.html"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            Administration
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Utilisateurs</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">
                <div class="mb-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_users"
                       class="btn btn-primary d-flex align-items-center"><i
                            class="ti ti-circle-plus me-2"></i>Ajout Utilisateur</a>
                </div>
            </div>
        </div>
        <!-- /Breadcrumb -->

        <!-- Performance Indicator list -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Liste des Utilisateurs</h5>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">
                    <div class="me-3">
                        <div class="input-icon-end position-relative">
                            <input type="text" class="form-control date-range bookingrange"
                                   placeholder="Recherche...">
                            <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table" v-cloak>
                        <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Date création</th>
                            <th>Date modif.</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(data, index) in allUsers">
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center file-name-icon">
                                    <a href="#" class="avatar avatar-md avatar-rounded">
                                        <img src="{{asset("assets/img/profiles/avatar-05.jpg")}}" class="img-fluid" alt="img">
                                    </a>
                                    <div class="ms-2">
                                        <h6 class="fw-medium"><a href="#">@{{ data.name }}</a></h6>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @{{ data.email }}
                            </td>
                            <td>
                                @{{ data.created_at }}
                            </td>
                            <td>
                                @{{ data.updated_at }}
                            </td>
                            <td>
                                <span :class="data.role ==='admin' ? 'badge-pink-transparent' : 'badge-info-transparent'"
                                      class=" badge badge-md p-2 fs-10">@{{ data.role }}</span>
                            </td>
                            <td>
                                <span class="badge badge-success d-inline-flex align-items-center badge-xs">
                                    <i class="ti ti-point-filled me-1"></i>Active
                                </span>
                            </td>
                            <td>
                                <div class="action-icon d-inline-flex">
                                    <a href="#" class="me-2" @click="getAccess(data)"><i :class="{'text-gray-3':data.role==='admin'}" class="ti ti-shield"></i></a>
                                    <a href="#" class="me-2" @click="editUser(data)"><i  class="ti ti-edit"></i></a>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#delete_modal"><i
                                            class="ti ti-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /Performance Indicator list -->


        <!-- /Modal create User -->
        <div class="modal fade" id="add_users">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Création compte utilisateur</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="createUser">
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom d'utilisateur</label>
                                        <input type="text" class="form-control" v-model="form.name" placeholder="ex: Gaston">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="text" v-model="form.email" class="form-control" placeholder="exemple@domain">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Mot de passe</label>
                                        <div class="pass-group">
                                            <input type="password" v-model="form.password" placeholder="***************" class="pass-input form-control">
                                            <span class="ti toggle-password ti-eye-off"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rôle</label>
                                        <select class="form-select" v-model="form.role">
                                            <option value="" hidden selected>--Sélectionner un rôle</option>
                                            <option v-for="(data, i) in allRoles" :value="data.name">@{{ data.name }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-white border me-2"
                                    data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary" :disabled="isLoading">
                                @{{ isLoading ? "Enregistrement..." : "Enregistrer" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- /Modal access -->
        <div class="modal fade" id="access_users">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Attribution accès utilisateur</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="addAccess">
                        <div class="modal-body pb-0">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead class="thead-light">
                                    <tr>
                                        <th>Module Permissions</th>
                                        <th v-for="col in ['voir', 'créer', 'modifier', 'supprimer', 'importer', 'exporter']" :key="col">@{{ col.charAt(0).toUpperCase() + col.slice(1) }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="module in allActions" :key="module.entity">
                                        <td>
                                            <h6 class="fs-14 fw-normal text-gray-9">@{{ module.label }}</h6>
                                        </td>
                                        <td v-for="col in ['view', 'create', 'update', 'delete', 'import', 'export']" :key="col">
                                            <div class="form-check form-check-md">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    :value="`${module.entity}.${col}`"
                                                    v-model="form.permissions"
                                                    :disabled="!module.actions.some(a => a.action === col)"
                                                >
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-white border me-2" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-info" :disabled="isLoading">
                                @{{ isLoading ? "Mise à jour..." : "Mettre à jour" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/user.js") }}"></script>
@endpush
