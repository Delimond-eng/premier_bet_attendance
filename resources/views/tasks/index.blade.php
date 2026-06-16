@extends("layouts.app")

@section("content")
    <style>
        #task-offcanvas, #task-details-offcanvas {
            width: 650px !important;
            max-width: 90%;
        }
        .evidence-img-container {
            position: relative;
            width: 100%;
            height: 150px;
            overflow: hidden;
            border-radius: 8px;
            background: #f0f0f0;
        }
        .evidence-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            max-width: 250px;
        }
    </style>

    <div class="content" id="TaskApp" v-cloak>
        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Gestion des Tâches</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Opérations</li>
                        <li class="breadcrumb-item active" aria-current="page">Gestion des Tâches</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="mb-2">
                    <a href="javascript:void(0);" class="btn btn-primary d-flex align-items-center" @click="openCreateOffcanvas">
                        <i class="ti ti-circle-plus me-2"></i>Nouvelle Mission
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistiques rapides -->
        <div class="row">
            <div class="col-lg-3 col-md-6 d-flex" v-for="stat in quickStats" :key="stat.label">
                <div class="card flex-fill">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center overflow-hidden">
                            <div>
                                <span :class="'avatar avatar-lg rounded-circle ' + stat.bgClass">
                                    <i :class="stat.icon"></i>
                                </span>
                            </div>
                            <div class="ms-2 overflow-hidden">
                                <p class="fs-12 fw-medium mb-1 text-truncate">@{{ stat.label }}</p>
                                <h4>@{{ stat.value }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des tâches -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Registre des interventions technique</h5>
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-fill" style="width: 260px;">
                        <select class="form-control" id="filter-station-id" v-model="filters.station_id">
                            <option value="">Toutes les stations</option>
                        </select>
                    </div>
                    <button class="btn btn-white border" @click="fetchTasks" :disabled="isLoading">
                        <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                        Filtrer
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="tasks-table">
                        <thead class="thead-light">
                            <tr>
                                <th>Tâche</th>
                                <th>Station</th>
                                <th>Assignation</th>
                                <th>Priorité</th>
                                <th>Échéance</th>
                                <th>Progression</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="task in tasks" :key="task.id">
                                <td>
                                    <div class="fw-bold">@{{ task.title }}</div>
                                    <small class="text-muted text-truncate-2" :title="task.description">@{{ task.description || 'N/A' }}</small>
                                </td>
                                <td>@{{ task.station?.name }}</td>
                                <td>
                                    <div v-if="task.is_global" class="badge badge-soft-info">Global</div>
                                    <div v-else class="avatar-list-stacked">
                                        <span v-for="agent in task.agents" :key="agent.id"
                                              class="avatar avatar-xs avatar-rounded"
                                              :class="agent.photo ? '' : 'bg-info-transparent border-info text-info'"
                                              data-bs-toggle="tooltip"
                                              :title="agent.fullname">
                                            <img v-if="agent.photo" :src="agent.photo" alt="img">
                                            <span v-else>@{{ agent.fullname.charAt(0).toUpperCase() }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span :class="getPriorityBadge(task.priority)">@{{ task.priority.toUpperCase() }}</span>
                                </td>
                                <td>
                                    <div :class="task.is_overdue ? 'text-danger fw-bold' : ''">
                                        @{{ formatDate(task.due_date) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center" style="min-width: 100px;">
                                        <div class="progress flex-fill me-2" style="height: 5px;">
                                            <div class="progress-bar bg-success" :style="{ width: task.progress + '%' }"></div>
                                        </div>
                                        <small class="fw-bold">@{{ task.progress }}%</small>
                                    </div>
                                </td>
                                <td>
                                    <span :class="getStatusBadge(task.status)">@{{ formatStatus(task.status) }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-soft-secondary btn-sm" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                                            <li><a class="dropdown-item" href="javascript:void(0);" @click="viewDetails(task)"><i class="ti ti-eye me-2 text-primary"></i>Détails & Preuves</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);" @click="editTask(task)"><i class="ti ti-edit me-2 text-info"></i>Modifier</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="javascript:void(0);" @click="deleteTask(task)"><i class="ti ti-trash me-2"></i>Supprimer</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Offcanvas Création / Modification -->
        <div class="sidebar-themesettings offcanvas offcanvas-end" id="task-offcanvas" tabindex="-1">
            <div class="offcanvas-header d-flex align-items-center justify-content-between bg-dark">
                <div>
                    <h3 class="mb-1 text-white">@{{ form.id ? 'Modifier la mission' : 'Nouvelle mission' }}</h3>
                    <p class="text-light mb-0">Configuration technique RD Tech</p>
                </div>
                <a href="#" class="custom-btn-close d-flex align-items-center justify-content-center" data-bs-dismiss="offcanvas">
                    <i class="ti ti-x"></i>
                </a>
            </div>
            <div class="offcanvas-body p-4">
                <form @submit.prevent="saveTask">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Titre de la mission <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" v-model="form.title" placeholder="ex: Installation Caméra IP Entrée" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Station <span class="text-danger">*</span></label>
                                <select class="form-control" id="form-station-id" v-model="form.station_id" required>
                                    <option value="">Choisir un site</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Priorité</label>
                                <select class="form-select" v-model="form.priority">
                                    <option value="low">Basse</option>
                                    <option value="medium">Moyenne</option>
                                    <option value="high">Haute / Urgente</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Date de début</label>
                                <input type="date" class="form-control" v-model="form.start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Échéance</label>
                                <input type="date" class="form-control" v-model="form.due_date" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Description technique</label>
                        <textarea class="form-control" v-model="form.description" rows="3" placeholder="Détails de l'intervention..."></textarea>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" v-model="form.is_global" id="is_global">
                            <label class="form-check-label fw-bold" for="is_global">Tâche globale (tous les agents du site)</label>
                        </div>

                        <div v-show="!form.is_global" class="p-3 border rounded bg-light-200 mt-2">
                            <label class="form-label fw-bold small text-uppercase">Techniciens affectés</label>
                            <select class="form-control" id="form-agent-ids" v-model="form.agent_ids" multiple>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark d-flex justify-content-between align-items-center">
                            Check-list des étapes
                            <button type="button" class="btn btn-xs btn-primary" @click="addSubtask"><i class="ti ti-plus"></i></button>
                        </label>
                        <div class="subtask-list mt-2">
                            <div v-for="(st, index) in form.subtasks" :key="index" class="input-group mb-2">
                                <input type="text" class="form-control" v-model="form.subtasks[index]" placeholder="ex: Tirage de câble">
                                <button type="button" class="btn btn-outline-danger" @click="removeSubtask(index)"><i class="ti ti-trash"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="offcanvas-footer border-top pt-3 mt-4">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="offcanvas">Annuler</button>
                            <button type="submit" class="btn btn-primary flex-fill" :disabled="isSaving">
                                <span v-if="isSaving" class="spinner-border spinner-border-sm me-2"></span>
                                @{{ form.id ? 'Mettre à jour' : 'Créer la mission' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Offcanvas Détails & Preuves -->
        <div class="sidebar-themesettings offcanvas offcanvas-end" id="task-details-offcanvas" tabindex="-1">
            <div class="offcanvas-header d-flex align-items-center justify-content-between bg-primary">
                <div>
                    <h3 class="mb-1 text-white">Détails de la mission</h3>
                </div>
                <a href="#" class="custom-btn-close d-flex align-items-center justify-content-center" data-bs-dismiss="offcanvas">
                    <i class="ti ti-x"></i>
                </a>
            </div>
            <div class="offcanvas-body p-0" v-if="selectedTask.id">
                <div class="p-4 border-bottom bg-light-200">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h4 class="fw-bold mb-0">@{{ selectedTask.title }}</h4>
                        <span :class="getStatusBadge(selectedTask.status)">@{{ formatStatus(selectedTask.status) }}</span>
                    </div>
                    <p class="text-muted mb-3">@{{ selectedTask.description || 'Aucune description fournie.' }}</p>

                    <div class="row g-3">
                        <div class="col-6">
                            <div class="small text-muted">Station</div>
                            <div class="fw-medium"><i class="ti ti-map-pin me-1"></i>@{{ selectedTask.station?.name }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Priorité</div>
                            <div :class="getPriorityBadge(selectedTask.priority)">@{{ selectedTask.priority.toUpperCase() }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Début</div>
                            <div class="fw-medium">@{{ formatDate(selectedTask.start_date) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-muted">Échéance</div>
                            <div class="fw-medium" :class="selectedTask.is_overdue ? 'text-danger' : ''">@{{ formatDate(selectedTask.due_date) }}</div>
                        </div>
                    </div>
                </div>

                <div class="p-4 border-bottom">
                    <h5 class="fw-bold mb-3 fs-14 text-uppercase">Check-list d'intervention</h5>
                    <ul class="list-group list-group-flush border-0">
                        <li v-for="st in selectedTask.subtasks" :key="st.id" class="list-group-item d-flex align-items-center border-0 px-0 py-2 bg-transparent">
                            <i :class="st.is_completed ? 'ti ti-checkbox text-success fs-18' : 'ti ti-square text-muted fs-18'" class="me-3"></i>
                            <div class="flex-fill">
                                <div :class="st.is_completed ? 'text-decoration-line-through text-muted' : 'fw-medium text-dark'">@{{ st.title }}</div>
                                <small v-if="st.completed_at" class="text-muted fs-10">Fait le @{{ formatDateTime(st.completed_at) }}</small>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="p-4">
                    <h5 class="fw-bold mb-3 fs-14 text-uppercase text-primary">Preuves Terrain (Photos)</h5>
                    <div class="row g-3">
                        <div class="col-md-6" v-for="ev in selectedTask.evidences" :key="ev.id">
                            <div class="card shadow-sm border mb-0">
                                <div class="evidence-img-container">
                                    <img :src="'/storage/' + ev.image_path" class="img-fluid" @click="zoomImage('/storage/' + ev.image_path)" style="cursor: zoom-in;">
                                </div>
                                <div class="card-body p-2">
                                    <h6 class="fs-12 mb-1">@{{ ev.agent?.fullname }}</h6>
                                    <p v-if="ev.note" class="small text-muted mb-1 italic">"@{{ ev.note }}"</p>
                                    <small class="text-muted fs-10">@{{ formatDateTime(ev.created_at) }}</small>
                                </div>
                            </div>
                        </div>
                        <div v-if="!selectedTask.evidences || selectedTask.evidences.length === 0" class="col-12 text-center py-4 bg-light rounded border border-dashed">
                            <i class="ti ti-camera-off fs-24 text-muted mb-2"></i>
                            <p class="text-muted small">Aucune preuve photo reçue.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
<script>
    window.__STATIONS__ = @json($stations);
    window.__AGENTS__ = @json($agents);
</script>
<script type="module" src="{{ asset('assets/js/scripts/tasks.js') }}"></script>
@endpush
