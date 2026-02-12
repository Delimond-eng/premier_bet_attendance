@extends("layouts.app")

@section("content")
<div class="content">
    <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
        <div class="my-auto mb-2">
            <h2 class="mb-1">Pointages Globaux</h2>
            <nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item active">Historique des Présences</li></ol></nav>
        </div>
        <div class="d-flex align-items-center flex-wrap">
            <div class="me-2 mb-2">
                <input type="date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
            <div class="mb-2">
                <button class="btn btn-white d-inline-flex align-items-center"><i class="ti ti-file-export me-1"></i>Exporter Excel</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Journal de Pointage - Aujourd'hui</h5>
            <div class="input-group w-25">
                <input type="text" class="form-control" placeholder="Rechercher agent...">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Agent</th>
                            <th>Station</th>
                            <th>Date</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Durée</th>
                            <th>Retard</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2"><img src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" class="rounded-circle"></span>
                                    <div><h6 class="mb-0">Mamba Salomon</h6><small>AGT-1022</small></div>
                                </div>
                            </td>
                            <td>Station Gombe</td>
                            <td>{{ date('d/m/Y') }}</td>
                            <td class="text-success fw-bold">07:02</td>
                            <td class="text-danger fw-bold">16:35</td>
                            <td>9h 33m</td>
                            <td><span class="badge badge-soft-success">Non</span></td>
                            <td><span class="badge badge-success">Complet</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2"><img src="{{ asset('assets/img/profiles/avatar-02.jpg') }}" class="rounded-circle"></span>
                                    <div><h6 class="mb-0">Kabamba Lucie</h6><small>AGT-1045</small></div>
                                </div>
                            </td>
                            <td>Entrepôt Limete</td>
                            <td>{{ date('d/m/Y') }}</td>
                            <td class="text-success fw-bold">07:45</td>
                            <td class="text-muted">--:--</td>
                            <td>--</td>
                            <td><span class="badge badge-soft-danger">Oui (45m)</span></td>
                            <td><span class="badge badge-warning">En poste</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2"><img src="{{ asset('assets/img/profiles/avatar-03.jpg') }}" class="rounded-circle"></span>
                                    <div><h6 class="mb-0">Ngoy Christian</h6><small>AGT-1088</small></div>
                                </div>
                            </td>
                            <td>Station Gombe</td>
                            <td>{{ date('d/m/Y') }}</td>
                            <td class="text-muted">--:--</td>
                            <td class="text-muted">--:--</td>
                            <td>--</td>
                            <td>--</td>
                            <td><span class="badge badge-danger">Absent</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
