@extends("layouts.app")

@section("content")
    <div class="content">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Journal d'accès</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Administration</li>
                        <li class="breadcrumb-item active" aria-current="page">Logs</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <p class="mb-0 text-muted">
                    Module en attente (à brancher sur une table de logs / audit).
                </p>
            </div>
        </div>
    </div>
@endsection

