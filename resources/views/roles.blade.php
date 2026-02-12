@extends("layouts.app")

@section("content")
    <div class="content" id="App">
        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Roles</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            Administration
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Roles</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">
                <div class="mb-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#role-create"
                       class="btn btn-primary d-flex align-items-center"><i
                            class="ti ti-circle-plus me-2"></i>Ajouter rôles</a>
                </div>
            </div>
        </div>
        <!-- /Breadcrumb -->

        <!-- Assets Lists -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Liste des Rôles</h5>
            </div>
            <div class="card-body p-0" v-cloak>
                <div class="custom-datatable-filter table-responsive">
                    <table class="table">
                        <thead class="thead-light rounded-0">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Role</th>
                            <th>Date creation</th>
                            <th>Date modification</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(data, index) in allRoles">
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td>@{{ data.name }}</td>
                            <td>@{{ data.created_at }} </td>
                            <td>@{{ data.updated_at }} </td>
                            <td>
                                <span class="badge badge-success d-inline-flex align-items-center badge-xs">
                                    <i class="ti ti-point-filled me-1"></i>Active
                                </span>
                            </td>
                            <td>
                                <div class="action-icon d-inline-flex">
                                    <a href="#"  @click="editRole(data)" class="me-2"><i :class="{'text-gray-3':data.name==='admin'}" class="ti ti-edit"></i></a>
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#delete_modal"><i
                                            class="ti ti-trash" :class="{'text-gray-3':data.name==='admin'}"></i></a>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- Edit  Users -->
        <div class="modal fade" id="role-create">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Création Rôle utilisateur</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal"
                                aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="createRole">
                        <div class="modal-body pb-0">
                            <div class="mb-3">
                                <label class="form-label">Rôle libellé</label>
                                <input v-model="formRole.name" type="text" class="form-control" placeholder="ex: Administrateur RH" required>
                            </div>

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
                                                    v-model="formRole.permissions"
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
                            <button v-if="formRole.role_id" type="submit" class="btn btn-info" :disabled="isLoading">
                                @{{ isLoading ? "Mise à jour..." : "Mettre à jour" }}
                            </button>
                            <button v-else type="submit" class="btn btn-primary" :disabled="isLoading">
                                @{{ isLoading ? "Enregistrement..." : "Enregistrer" }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- /Edit  Users -->

    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/user.js") }}"></script>
@endpush
