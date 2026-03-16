@extends("layouts.app")

@section("content")
    <div class="content" id="planning-app" v-cloak>
        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Gestion de planning des rotations</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="/"><i class="ti ti-smart-home"></i></a></li>
                        <li class="breadcrumb-item">Admin</li>
                        <li class="breadcrumb-item active" aria-current="page">Gestion planning</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <div style="min-width: 260px;">
                        <select class="form-select" v-model="stationId" @change="fetchPlanning()">
                            <option value="">Toutes les stations d'affectation</option>
                            <option v-for="s in stations" :key="s.id" :value="String(s.id)">@{{ s.name }}</option>
                        </select>
                    </div>
                    <div style="min-width: 190px;">
                        <input type="date" class="form-control" v-model="weekDate" @change="fetchPlanning()">
                    </div>
                    <button type="button" class="btn btn-primary d-flex align-items-center" @click="openImportModal">
                        <i class="ti ti-upload me-2"></i> Charger le planning excel
                    </button>
                    <button type="button" class="btn btn-info d-flex align-items-center ms-2" @click="openModal">
                        <i class="ti ti-user-edit me-2"></i> Planning Agent
                    </button>
                </div>
            </div>
        </div>

        <!-- Planning Table -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Planning de rotation regroupé par station de travail</h5>
                <div class="btn-group shadow-sm">
                    <button type="button" class="btn btn-white border" @click="goPrev" :disabled="isLoading || !canPrev">
                        <i class="ti ti-chevron-left me-1"></i>Précédent
                    </button>
                    <button type="button" class="btn btn-white border" @click="goNext" :disabled="isLoading || !canNext">
                        Suivant<i class="ti ti-chevron-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-info-light border" @click="duplicatePrevWeek" :disabled="isLoading || !canDuplicatePrev">
                        <i class="ti ti-copy me-1"></i>Régénérer le planning
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-center mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="text-start" style="min-width: 200px;">Agent</th>
                                <th v-for="d in days" :key="d.date">@{{ d.label }}</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="isLoading"><td :colspan="days.length + 2" class="p-4 text-muted"><div class="spinner-border spinner-border-sm me-2"></div> Chargement...</td></tr>
                            <tr v-else-if="stationGroups.length === 0"><td :colspan="days.length + 2" class="p-4 text-muted">Aucun planning cette semaine.</td></tr>
                            <template v-for="g in stationGroups">
                                <tr :key="'station-' + g.key" class="table-primary">
                                    <td :colspan="days.length + 2" class="text-start fw-bold py-2 text-uppercase">
                                        <i class="ti ti-building me-1"></i> @{{ g.station_name }}
                                    </td>
                                </tr>
                                <tr v-for="r in g.rows" :key="'row-' + g.key + '-' + r.agent.id">
                                    <td class="text-start">
                                        <strong>@{{ r.agent.fullname }}</strong><br>
                                        <small class="text-muted">@{{ r.agent.matricule }}</small>
                                    </td>
                                    <td v-for="d in days" :key="d.date">
                                        <div v-if="r.days[d.date]">
                                            <span v-if="r.days[d.date].status === 'off'" class="badge bg-soft-danger text-danger">OFF</span>
                                            <div v-else-if="r.days[d.date].status === 'work'">
                                                <div class="fw-bold">@{{ r.days[d.date].label }}</div>
                                            </div>
                                            <span v-else class="text-muted">--</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-soft-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="javascript:void(0);" @click="editIndividualPlanning(r.agent, g.key)"><i class="ti ti-edit me-2"></i>Modifier</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="javascript:void(0);" @click="deleteAgentPlanning(r.agent)"><i class="ti ti-trash me-2"></i>Supprimer</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Import Modal -->
        <div class="modal fade" id="importModal" tabindex="-1" ref="importModalEl">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Importer le planning hebdomadaire</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-soft-primary mb-3">Semaine cible : <strong>@{{ weekDate }}</strong></div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">1. Station d'affectation</label>
                            <p class="text-muted small">Les agents seront affectés à cette station et leur planning y sera rattaché.</p>
                            <select class="form-select" v-model="importModal.stationId">
                                <option value="">Choisir une station...</option>
                                <option v-for="s in stations" :key="s.id" :value="s.id">@{{ s.name }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">2. Fichier Excel/CSV</label>
                            <input type="file" class="form-control" accept=".xlsx,.xls,.csv" @change="onImportFileChange">
                            <small class="text-muted d-block mt-1">Format requis: MATRICULE + LUNDI à DIMANCHE</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" @click="submitImport" :disabled="!importModal.file || !importModal.stationId || isUploading">
                            <span v-if="isUploading" class="spinner-border spinner-border-sm me-1"></span>
                            @{{ isUploading ? 'Importation...' : 'Lancer l\'importation' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agent Planning Modal -->
        <div class="modal fade" id="agentModal" tabindex="-1" ref="modalEl">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Planning Individuel</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">1. Station d'affectation actuelle</label>
                                <select class="form-select" v-model="modal.stationId" ref="modalStationSelect">
                                    <option value="">Choisir...</option>
                                    <option v-for="s in stations" :key="s.id" :value="s.id">@{{ s.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">2. Agent</label>
                                <select class="form-select" v-model="modal.agentId" ref="modalAgentSelect" :disabled="!modal.stationId">
                                    <option value="">Choisir...</option>
                                    <option v-for="a in modal.agents" :key="a.id" :value="a.id">@{{ a.fullname }} (@{{ a.matricule }})</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">3. Station de travail (Rotation)</label>
                                <select class="form-select" v-model="modal.targetStationId" :disabled="!modal.agentId">
                                    <option value="">Utiliser sa station d'affectation</option>
                                    <option v-for="s in stations" :key="s.id" :value="s.id">@{{ s.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div v-if="modal.agentId" class="border rounded p-3 bg-light shadow-sm">
                            <h6 class="mb-3 border-bottom pb-2">Configuration de la semaine : <strong>@{{ weekDate }}</strong></h6>
                            <div class="table-responsive">
                                <table class="table table-bordered bg-white text-center">
                                    <thead class="table-light">
                                        <tr><th v-for="d in days" :key="d.date">@{{ d.label }}<br><small class="text-muted">@{{ d.date }}</small></th></tr>
                                    </thead>
                                    <tbody>
                                        <tr><td v-for="d in days" :key="d.date" class="align-middle">
                                            <select class="form-select form-select-sm mb-1" v-model="modal.plannings[d.date]">
                                                <option :value="null">OFF</option>
                                                <option v-for="h in modal.horaires" :key="h.id" :value="h.id">@{{ h.libelle }} (@{{ h.started_at.substring(0,5) }})</option>
                                            </select>
                                            <button v-if="modal.plannings[d.date]" class="btn btn-xs btn-outline-danger w-100 mt-1" @click="modal.plannings[d.date] = null">
                                                <i class="ti ti-trash-x"></i> Vider
                                            </button>
                                        </td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-white border" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" @click="saveAgentPlanning" :disabled="!modal.agentId || modal.isSaving">
                            <span v-if="modal.isSaving" class="spinner-border spinner-border-sm me-1"></span>
                            @{{ modal.isSaving ? 'Enregistrement...' : 'Enregistrer le planning' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("scripts")
<script>
(function () {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    new Vue({
        el: '#planning-app',
        data() {
            return {
                stations: [], stationId: '', weekDate: new Date().toISOString().slice(0, 10),
                isLoading: false, isUploading: false, canPrev: false, canNext: false, canDuplicatePrev: false,
                days: [], stationGroups: [],
                importModal: { stationId: '', file: null, bsModal: null },
                modal: { stationId: '', agentId: '', targetStationId: '', agents: [], horaires: [], plannings: {}, isSaving: false, bsModal: null }
            };
        },
        watch: {
            'modal.stationId'(val) {
                this.modal.agentId = ''; this.modal.agents = [];
                if (val) {
                    this.loadModalAgents(val);
                } else {
                    this.modal.horaires = [];
                }
                this.$nextTick(() => { $(this.$refs.modalAgentSelect).val('').trigger('change'); });
            },
            'modal.targetStationId'(val) {
                // If no rotation station selected, default to the affectation station
                const sid = val || this.modal.stationId;
                if (sid) {
                    this.loadModalHoraires(sid);
                } else {
                    this.modal.horaires = [];
                }
            },
            'modal.agentId'(val) {
                this.modal.plannings = {};
                if (val) {
                    const agent = this.modal.agents.find(a => a.id == val);
                    const existing = this.findExistingPlanning(val);

                    let targetId = '';
                    if (existing) {
                        for (let date in existing) {
                            if (existing[date].site_id) {
                                targetId = existing[date].site_id;
                                break;
                            }
                        }
                    }
                    if (!targetId && agent) targetId = agent.site_id;

                    this.modal.targetStationId = targetId || '';

                    this.days.forEach(d => {
                        let hId = null;
                        if (existing?.[d.date]?.status === 'work') {
                            hId = existing[d.date].horaire_id;
                        }
                        this.$set(this.modal.plannings, d.date, hId);
                    });
                } else {
                    this.modal.targetStationId = '';
                }
            }
        },
        mounted() { this.loadStations().then(() => this.fetchPlanning()); this.initSelect2(); },
        methods: {
            initSelect2() {
                const self = this;
                this.$nextTick(() => {
                    $(this.$refs.modalStationSelect).select2({ dropdownParent: $(this.$refs.modalEl) }).on('change', function() { self.modal.stationId = $(this).val(); });
                    $(this.$refs.modalAgentSelect).select2({ dropdownParent: $(this.$refs.modalEl) }).on('change', function() { self.modal.agentId = $(this).val(); });
                });
            },
            openModal() { if (!this.modal.bsModal) this.modal.bsModal = new bootstrap.Modal(this.$refs.modalEl); this.modal.bsModal.show(); },
            openImportModal() { if (!this.importModal.bsModal) this.importModal.bsModal = new bootstrap.Modal(this.$refs.importModalEl); this.importModal.bsModal.show(); },
            onImportFileChange(e) { this.importModal.file = e.target.files[0] || null; },
            async submitImport() {
                this.isUploading = true;
                try {
                    const fd = new FormData(); fd.append('file', this.importModal.file); fd.append('start_date', this.weekDate);
                    fd.append('station_id', this.importModal.stationId);
                    const res = await fetch('/rh/planning/import-week', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf() } });
                    const json = await res.json();
                    if (!res.ok) throw new Error(json.errors?.join('\n') || 'Import échoué');
                    Swal.fire('Succès', 'Planning importé avec succès', 'success'); this.importModal.bsModal.hide(); this.fetchPlanning();
                } catch (e) { Swal.fire('Erreur', e.message, 'error'); } finally { this.isUploading = false; }
            },
            async loadStations() {
                try {
                    const res = await fetch('/stations/list'); const json = await res.json();
                    this.stations = json?.sites?.map(s => ({id: s.id, name: s.name})) || [];
                } catch (e) { console.error(e); }
            },
            async loadModalAgents(sid) {
                try {
                    const res = await fetch(`/rh/agents-for-station?station_id=${sid}`); const json = await res.json();
                    this.modal.agents = json?.agents || [];
                } catch (e) { console.error(e); }
            },
            async loadModalHoraires(sid) {
                try {
                    const res = await fetch(`/rh/horaires?site_id=${sid}`); const json = await res.json();
                    this.modal.horaires = json?.horaires || [];
                } catch (e) { console.error(e); }
            },
            findExistingPlanning(aid) {
                for (let g of this.stationGroups) { const r = g.rows.find(row => row.agent.id == aid); if (r) return r.days; } return null;
            },
            editIndividualPlanning(agent, groupStationId) {
                this.modal.stationId = agent.site_id;
                this.$nextTick(() => {
                    $(this.$refs.modalStationSelect).val(agent.site_id).trigger('change');
                    setTimeout(() => {
                        this.modal.agentId = agent.id;
                        $(this.$refs.modalAgentSelect).val(agent.id).trigger('change');
                        // Crucially, use the station from the table as the rotation station
                        this.modal.targetStationId = groupStationId;
                        this.openModal();
                    }, 300);
                });
            },
            async saveAgentPlanning() {
                this.modal.isSaving = true;
                try {
                    const payload = {
                        agent_id: this.modal.agentId, start_date: this.weekDate,
                        plannings: this.days.map(d => ({
                            date: d.date,
                            horaire_id: this.modal.plannings[d.date],
                            site_id: this.modal.targetStationId || null,
                            is_rest_day: !this.modal.plannings[d.date]
                        }))
                    };
                    const res = await fetch('/rh/planning/agent/update', { method: 'POST', body: JSON.stringify(payload), headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' } });
                    if (!res.ok) throw new Error('Erreur de sauvegarde');
                    Swal.fire('Succès', 'Planning enregistré', 'success'); this.modal.bsModal.hide(); this.fetchPlanning();
                } catch (e) { Swal.fire('Erreur', e.message, 'error'); } finally { this.modal.isSaving = false; }
            },
            async fetchPlanning() {
                this.isLoading = true;
                try {
                    const res = await fetch(`/rh/planning/week?date=${this.weekDate}&station_id=${this.stationId}`);
                    const json = await res.json(); this.days = json?.days || []; this.stationGroups = json?.stations || [];
                    this.refreshNav();
                } catch (e) { console.error(e); this.stationGroups = []; } finally { this.isLoading = false; }
            },
            async refreshNav() {
                const addDays = (d, n) => { let date = new Date(d); date.setDate(date.getDate() + n); return date.toISOString().slice(0, 10); };
                const check = async (d) => { const res = await fetch(`/rh/planning/week?date=${d}&station_id=${this.stationId}&exists_only=1`); const json = await res.json(); return !!json?.exists; };
                const [p, n] = await Promise.all([check(addDays(this.weekDate, -7)), check(addDays(this.weekDate, 7))]);
                this.canPrev = p; this.canNext = n; this.canDuplicatePrev = p;
            },
            goPrev() { if (this.canPrev) { let d = new Date(this.weekDate); d.setDate(d.getDate() - 7); this.weekDate = d.toISOString().slice(0, 10); this.fetchPlanning(); } },
            goNext() { if (this.canNext) { let d = new Date(this.weekDate); d.setDate(d.getDate() + 7); this.weekDate = d.toISOString().slice(0, 10); this.fetchPlanning(); } },
            async duplicatePrevWeek() {
                const res = await Swal.fire({ title: 'Confirmer', text: 'Reconduire le planning de la semaine passée ? Cela écrasera les données actuelles.', icon: 'question', showCancelButton: true });
                if (!res.isConfirmed) return;
                this.isLoading = true;
                try {
                    const res2 = await fetch('/rh/planning/duplicate-week', { method: 'POST', body: JSON.stringify({ station_id: this.stationId || null, current_week_date: this.weekDate }), headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' } });
                    if (!res2.ok) throw new Error('Échec');
                    Swal.fire('Succès', 'Reconduit', 'success'); this.fetchPlanning();
                } catch (e) { Swal.fire('Erreur', 'Impossible de régénérer', 'error'); } finally { this.isLoading = false; }
            },
            async deleteAgentPlanning(agent) {
                const res = await Swal.fire({ title: 'Confirmer', text: `Supprimer tout le planning de ${agent.fullname} ?`, icon: 'warning', showCancelButton: true });
                if (!res.isConfirmed) return;
                this.isLoading = true;
                try {
                    await fetch('/rh/planning/agent/delete', { method: 'POST', body: JSON.stringify({ agent_id: agent.id, start_date: this.weekDate }), headers: { 'X-CSRF-TOKEN': csrf(), 'Content-Type': 'application/json' } });
                    this.fetchPlanning();
                } catch (e) { Swal.fire('Erreur', '', 'error'); } finally { this.isLoading = false; }
            }
        }
    });
})();
</script>
@endpush
