@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak data-kind="retard">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Justifications (retard)</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Ressources humaines</li>
                        <li class="breadcrumb-item active" aria-current="page">Justification retard</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#justif_modal" @click="reset">
                    <i class="ti ti-circle-plus me-2"></i>Ajouter
                </button>
            </div>
        </div>

        @include("rh_partials.justifications")
    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/rh-justifications.js") }}"></script>
@endpush

