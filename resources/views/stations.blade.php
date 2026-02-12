@extends("layouts.app")

@section("content")
    <div class="content">

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
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <a href="#" data-bs-target="#add_station" data-bs-toggle="modal" class="btn btn-primary-gradient mb-2"> <i class="ti ti-plus"></i> Nouvelle station</a>
            </div>
        </div>
        <!-- /Breadcrumb -->

        <!-- start row -->
        <div class="row">
            <div class="col-xl-12">
                <!-- Candidate Hiring Analysis -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                            <h3 class="mb-0 card-title">Liste des stations</h3>
                            <div class="d-flex align-items-center gap-3">
                                <div class="input-group input-group-flat d-inline-flex me-2">
                                    <input type="text" class="form-control" placeholder="Recherche">
                                    <span class="input-group-text p-2">
                                        <i class="ti ti-search"></i>
                                    </span>
                                </div>
                                <div class="dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle btn btn-light rounded-pill text-dark dropdown-icon-none" data-bs-toggle="dropdown">
                                        <i class="ti ti-qrcode fs-16"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end p-3">
                                        <li>
                                            <a href="javascript:void(0);" class="dropdown-item rounded-1">Télécharger les qrcodes</a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive candidates-table">
                            <table class="table table-nowrap mb-0">
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
                                @for($i=0; $i<10;$i++)
                                    <tr>
                                        <td class="px-0 pe-5">
                                            <div class="d-flex align-items-center">
                                                <div>
                                                    <h6 class="fw-normal mb-1 fs-14"><a href="#">Marketing</a></h6>
                                                    <span class="fs-13 d-inline-flex align-items-center">Adresse de la stations</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="badge bg-info fs-13  rounded-xxl py-2">14</div>
                                        </td>
                                        <td>
                                            <div class="badge bg-success  fs-13 rounded-xxl py-2">08</div>
                                        </td>

                                        <td>
                                            <div class="badge bg-danger fs-13 rounded-xxl text-light py-2">14</div>
                                        </td>

                                        <td>
                                            <div class="badge bg-warning fs-13 rounded-xxl text-light py-2">14</div>
                                        </td>

                                        <td>
                                            <div class="action-icon d-inline-flex">
                                                <a href="#" class="me-2 text-info"><i class="ti ti-edit"></i></a>
                                                <a href="#" class="me-2 text-danger"><i class="ti ti-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                @endfor
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <!-- end row -->


        <div class="modal fade" id="add_station" aria-modal="true" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-md">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Création station</h4>
                        <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form>
                        <div class="modal-body pb-0">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Nom de la station<span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control" placeholder="ex: Direction Générale">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Adresse<span class="text-danger"> (facultatif)</span></label>
                                        <input type="text" class="form-control" placeholder="Adresse...">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre d'agents<span class="text-danger"> (facultatif)</span></label>
                                        <input type="number" class="form-control" placeholder="0">
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
