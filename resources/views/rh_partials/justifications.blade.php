<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5>Liste des justifications</h5>
        <span class="text-muted" v-if="isLoading">Chargement...</span>
    </div>
<div class="card-body">
    <div class="table-responsive">
        <table class="table" ref="table">
        <thead class="thead-light">
        <tr>
            <th>Agent</th>
            <th>Station</th>
            <th>Date</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="j in justifications" :key="j.id">
                    <td>
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-sm me-2">
                                <img :src="j.agent?.photo || 'https://smarthr.co.in/demo/html/template/assets/img/users/user-26.jpg'" class="rounded-circle" alt="img">
                            </span>
                            <div>
                                <h6 class="mb-0">@{{ j.agent?.fullname ?? '-' }}</h6>
                                <small class="text-muted">@{{ j.agent?.matricule ?? '' }}</small>
                            </div>
                        </div>
                    </td>
                    <td>@{{ j.presence?.assigned_station?.name ?? j.agent?.station?.name ?? '-' }}</td>
                    <td>@{{ j.date_reference_label ?? j.date_reference }}</td>
                    <td><span class="badge badge-soft-warning">@{{ kindLabel(j.kind) }}</span></td>
                    <td><span class="badge" :class="{'badge-success' : j.status ==='approved', 'badge-danger' : j.status ==='rejected', 'badge-warning' : j.status ==='pending',}">@{{ statusLabel(j.status) }}</span></td>
                    <td>
                        <a href="javascript:void(0);" class="me-2 text-info" @click="edit(j)"><i class="ti ti-edit"></i></a>
                        <a href="javascript:void(0);" class="text-danger" @click="remove(j)"><i class="ti ti-trash"></i></a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="justif_modal" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@{{ form.id ? 'Modification justification' : 'Création justification' }}</h4>
                <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <form @submit.prevent="save">
                <div class="modal-body pb-0">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Agent<span class="text-danger"> *</span></label>
                                <select class="form-select" v-model="form.agent_id">
                                    <option value="" hidden>--Sélectionner agent--</option>
                                    <option v-for="ag in agents" :key="ag.id" :value="ag.id">@{{ ag.fullname }} (@{{ ag.matricule }})</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Date<span class="text-danger"> *</span></label>
                                <input type="date" class="form-control" v-model="form.date_reference">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Justification<span class="text-danger"> *</span></label>
                                <textarea class="form-control" v-model="form.justification"></textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label">Statut</label>
                                <select class="form-select" v-model="form.status">
                                    <option value="pending">@{{ statusLabel("pending") }}</option>
                                    <option value="approved">@{{ statusLabel("approved") }}</option>
                                    <option value="rejected">@{{ statusLabel("rejected") }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" :disabled="isLoading">
                        @{{ isLoading ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
