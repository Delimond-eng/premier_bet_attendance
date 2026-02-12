@extends("layouts.app")


@section("content")

    <div class="content">

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

                <div class="mb-2">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#add_horaire" class="btn btn-primary d-flex align-items-center"><i class="ti ti-circle-plus me-2"></i>Ajout groupe</a>
                </div>

            </div>
        </div>
        <!-- /Breadcrumb -->

        <!-- Leads List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Liste des groupes des agents</h5>

            </div>
            <div class="card-body p-0">
                <div class="custom-datatable-filter table-responsive">
                    <table class="table datatable">
                        <thead class="thead-light">
                        <tr>
                            <th class="no-sort">
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Designation</th>
                            <th>Horaire</th>
                            <th>Temps</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>
                                <div class="form-check form-check-md">
                                    <input class="form-check-input" type="checkbox">
                                </div>
                            </td>
                            <td><h6 class="fs-14 fw-medium">Matinale</h6></td>
                            <td>
                                <div>
                                    <h6 class="fs-14 fw-medium">Matinale</h6>
                                    <small>De 07:30 à 16:30</small>
                                </div>
                            </td>
                            <td>
                                <div class=" d-flex align-items-center">
                                    <div class="progress me-2" role="progressbar" aria-label="Basic example" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="height: 5px; min-width: 80px;">
                                        <div class="progress-bar bg-info" style="width: 60%"></div>
                                    </div>
                                    <span class="fs-14 fw-normal">Matinale</span>
                                </div>
                            </td>
                            <td>
                                <div class="action-icon d-inline-flex">
                                    <a href="#" class="me-2 text-info"><i class="ti ti-edit"></i></a>
                                    <a href="#" class="text-danger"><i class="ti ti-trash"></i></a>
                                </div>
                            </td>
                        </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- /Leads List -->


        <div class="modal fade" id="add_horaire" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Création groupe agent</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form>
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Désignation<span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" placeholder="ex: Matinale">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Horaire<span class="text-danger"> *</span></label>
                                        <select>
                                            <option value="">Sélectionner horaire</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

@endsection
