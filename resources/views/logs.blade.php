@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
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
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap gap-2">
                <button class="btn btn-white border" @click="loadLogs" :disabled="isLoading">
                    <i class="ti ti-refresh me-1"></i> Actualiser
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Dernières activités des utilisateurs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-nowrap">
                        <thead class="thead-light">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Rôle</th>
                                <th>Station</th>
                                <th>Dernier accès</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md avatar-rounded me-2">
                                            <img src="{{asset("assets/img/avatar.jpg")}}" alt="img">
                                        </div>
                                        <div>
                                            <h6 class="mb-0">@{{ user.name }}</h6>
                                            <span class="fs-12 text-muted">@{{ user.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-md badge-info-transparent">@{{ user.role }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-purple" v-if="user.station">@{{ user.station.name }}</span>
                                    <span class="text-muted" v-else>Accès global</span>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium text-dark">@{{ formatDate(user.last_seen_at) }}</span>
                                        <small class="text-primary">@{{ getTimeAgo(user.last_seen_at) }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span v-if="user.last_seen_at && (new Date() - new Date(user.last_seen_at) < 300000)" class="badge badge-success d-inline-flex align-items-center badge-xs">
                                        <i class="ti ti-point-filled me-1"></i>En ligne
                                    </span>
                                    <span v-else class="badge badge-soft-secondary d-inline-flex align-items-center badge-xs">
                                        <i class="ti ti-point-filled me-1"></i>Hors ligne
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0 && !isLoading">
                                <td colspan="5" class="text-center p-4">Aucun utilisateur trouvé.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
    <script type="module" src="{{ asset("assets/js/scripts/logs.js") }}"></script>
@endpush
