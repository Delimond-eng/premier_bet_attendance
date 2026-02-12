@extends("layouts.app")


@section("content")
    <div class="content" id="App" v-cloak>

        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Gestion des agents</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            Employees
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Liste des agents</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">

                <div class="me-2 mb-2">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                            <i class="ti ti-file-export me-1"></i>Exporter
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-end p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-1"></i>Exporter en PDF</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-1"></i>Exporter en Excel </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mb-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_employee" class="btn btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Ajout agent</a>
                </div>

            </div>
        </div>
        <!-- /Breadcrumb -->

        <div class="row">

            <!-- Total Plans -->
            <div class="col-lg-3 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center overflow-hidden">
                            <div>
                                <span class="avatar avatar-lg bg-dark rounded-circle"><i class="ti ti-users"></i></span>
                            </div>
                            <div class="ms-2 overflow-hidden">
                                <p class="fs-12 fw-medium mb-1 text-truncate">Total agents</p>
                                <h4>@{{ stats.total }}</h4>
                            </div>
                        </div>
                        <div>
                            <span class="badge badge-soft-purple badge-sm fw-normal">
                                <i class="ti ti-arrow-wave-right-down"></i>
                                +19.01%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Total Plans -->

            <!-- Total Plans -->
            <div class="col-lg-3 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center overflow-hidden">
                            <div>
                                <span class="avatar avatar-lg bg-success rounded-circle"><i class="ti ti-user-share"></i></span>
                            </div>
                            <div class="ms-2 overflow-hidden">
                                <p class="fs-12 fw-medium mb-1 text-truncate">Actif</p>
                                <h4>@{{ stats.actif }}</h4>
                            </div>
                        </div>
                        <div>
                            <span class="badge badge-soft-primary badge-sm fw-normal">
                                <i class="ti ti-arrow-wave-right-down"></i>
                                +19.01%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Total Plans -->

            <!-- Inactive Plans -->
            <div class="col-lg-3 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center overflow-hidden">
                            <div>
                                <span class="avatar avatar-lg bg-danger rounded-circle"><i class="ti ti-user-pause"></i></span>
                            </div>
                            <div class="ms-2 overflow-hidden">
                                <p class="fs-12 fw-medium mb-1 text-truncate">InActif</p>
                                <h4>@{{ stats.inactif }}</h4>
                            </div>
                        </div>
                        <div>
                            <span class="badge badge-soft-dark badge-sm fw-normal">
                                <i class="ti ti-arrow-wave-right-down"></i>
                                +19.01%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Inactive Companies -->

            <!-- No of Plans  -->
            <div class="col-lg-3 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center overflow-hidden">
                            <div>
                                <span class="avatar avatar-lg bg-info rounded-circle"><i class="ti ti-user-plus"></i></span>
                            </div>
                            <div class="ms-2 overflow-hidden">
                                <p class="fs-12 fw-medium mb-1 text-truncate">Congés</p>
                                <h4>@{{ stats.conges }}</h4>
                            </div>
                        </div>
                        <div>
                            <span class="badge badge-soft-secondary badge-sm fw-normal">
                                <i class="ti ti-arrow-wave-right-down"></i>
                                +19.01%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /No of Plans -->
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>La liste de tous les agents pour tous les stations</h5>
                <div class="d-flex my-xl-auto right-content align-items-center flex-wrap row-gap-3">

                    <div class="dropdown me-3">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                            Stations
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-end p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Finance</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Developer</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Executive</a>
                            </li>
                        </ul>
                    </div>
                    <div class="dropdown me-3">
                        <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown">
                            Statut
                        </a>
                        <ul class="dropdown-menu  dropdown-menu-end p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Active</a>
                            </li>
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1">Inactive</a>
                            </li>
                        </ul>
                    </div>
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
                            <th>MATRICULE</th>
                            <th>NOM COMPLET</th>
                            <th>TELEPHONE</th>
                            <th>FONCTION</th>
                            <th>STATION AFFECTE</th>
                            <th>Date création</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                                <tr v-for="data in agents" :key="data.id">
                                    <td>
                                        <div class="form-check form-check-md">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#">@{{ data.matricule }}</a></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="#" class="avatar avatar-md" data-bs-toggle="modal" data-bs-target="#view_details">
                                                <img :src="data.photo ?? 'https://smarthr.co.in/demo/html/template/assets/img/users/user-26.jpg'" class="img-fluid rounded-circle" alt="img">
                                            </a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="#">@{{ data.fullname }}</a></p>
                                                <span class="fs-12">@{{ data.station?.name ?? '--' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>--</td>
                                    <td>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">--</a>
                                    </td>
                                    <td>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">@{{ data.station?.name ?? '--' }}</a>
                                    </td>
                                    <td>@{{ data.created_at }}</td>
                                    <td>
                                        <span class="badge badge-soft-success d-inline-flex align-items-center badge-xs" v-if="data.status === 'actif'">
                                            <i class="ti ti-point-filled me-1"></i>Actif
                                        </span>
                                        <span class="badge badge-soft-danger d-inline-flex align-items-center badge-xs" v-else>
                                            <i class="ti ti-point-filled me-1"></i>Inactif
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-icon d-inline-flex">
                                            <a href="#" class="me-2 text-info" data-bs-toggle="modal" data-bs-target="#edit_employee"><i class="ti ti-edit"></i></a>
                                            <a href="#" class="me-2 text-danger" data-bs-toggle="modal" data-bs-target="#delete_modal"><i class="ti ti-trash"></i></a>
                                            <a class="text-primary" :href="'/agents/view/attendances?agent_id='+data.id"><i class="ti ti-calendar-time"></i></a>
                                        </div>
                                    </td>
                                </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <div class="modal fade" id="add_employee"  aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="d-flex align-items-center">
                            <h4 class="modal-title me-2">Création agent</h4><span></span>
                        </div>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form @submit.prevent="saveAgent">
                        <div class="modal-body pb-0 ">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="d-flex align-items-center flex-wrap row-gap-3 border-1 border-light-subtle bg-light-200 w-100 rounded p-3 mb-4">
                                        <div class="d-flex align-items-center justify-content-center avatar avatar-xxl rounded-circle border border-dashed me-2 flex-shrink-0 text-dark frames">
                                            <i class="ti ti-photo text-gray-2 fs-16"></i>
                                        </div>
                                        <div class="profile-upload">
                                            <div class="mb-2">
                                                <h6 class="mb-1">Charger une photo</h6>
                                                <p class="fs-12"><sma>(facultatif)</sma></p>
                                            </div>
                                            <div class="profile-uploader d-flex align-items-center">
                                                <div class="drag-upload-btn btn btn-sm btn-info me-2">
                                                    Charger
                                                    <input type="file" class="form-control image-sign" @change="onPhotoChange">
                                                </div>
                                                <a href="javascript:void(0);" class="btn btn-light btn-sm">Annuler</a>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Matricule <span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" placeholder="ex:Emp_0001" v-model="createForm.matricule" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Nom complet<span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" v-model="createForm.fullname" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Statut</label>
                                        <select class="form-select" v-model="createForm.status">
                                            <option value="actif">Actif</option>
                                            <option value="inactif">Inactif</option>
                                        </select>
                                    </div>
                                </div>


                                <div class="col-md-6">
                                    <div class="mb-3 ">
                                        <label class="form-label">Sexe <span class="text-danger"> *</span></label>
                                        <div class="d-flex gap-2">
                                            <div class="form-check form-check-lg">
                                                <input class="form-check-input" value="Homme" type="radio" name="Radio" id="Radio-lg" checked="">
                                                <label class="form-check-label" for="Radio-lg">
                                                    Homme
                                                </label>
                                            </div>
                                            <div class="form-check form-check-lg">
                                                <input value="Femme" class="form-check-input" type="radio" name="Radio" id="Radio-lg" checked="">
                                                <label class="form-check-label" for="Radio-lg">
                                                    Femme
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Station <span class="text-danger"> *</span></label>
                                        <select class="form-select" v-model="createForm.site_id" required>
                                            <option value="">Sélectionner station</option>
                                            <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Groupe horaire<span class="text-danger"> *</span></label>
                                        <select class="form-select">
                                            <option value="">Sélectionner groupe</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-light border me-2" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary" :disabled="isSaving">
                                @{{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script>
        window.__SITES__ = @json($sites);
    </script>
    <script type="module" src="{{ asset("assets/js/scripts/agents.js") }}"></script>
@endpush
