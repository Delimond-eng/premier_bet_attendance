@extends("layouts.app")


@section("content")

    <div class="content" id="planning-app" v-cloak>

        <!-- Breadcrumb -->
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Gestion de planning des rotations</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">
                            Admin
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Gestion planning.</li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap ">

                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                    <div style="min-width: 260px;">
                        <select class="form-select" v-model="stationId" @change="fetchPlanning()">
                            <option value="">Toutes les stations</option>
                            <option v-for="s in stations" :key="s.id" :value="String(s.id)">
                                @{{ s.name }}
                            </option>
                        </select>
                    </div>

                    <div style="min-width: 190px;">
                        <input type="date" class="form-control" v-model="weekDate" @change="fetchPlanning()">
                    </div>


                    <div>
                        <input ref="fileInput" type="file" class="d-none" accept=".xlsx,.xls,.csv,.txt" @change="onFilePicked">
                        <button type="button" class="btn btn-primary d-flex align-items-center" :disabled="isUploading" @click="pickFile">
                            <i class="ti ti-upload me-2"></i>
                            <span v-if="!isUploading">Charger le planning excel</span>
                            <span v-else>Import...</span>
                        </button>
                    </div>

                    <button type="button" class="btn btn-info d-flex align-items-center ms-2" @click="openModal">
                        <i class="ti ti-user-edit me-2"></i> Planning Agent
                    </button>
                </div>

            </div>
        </div>
        <!-- /Breadcrumb -->

        <!-- Leads List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap row-gap-3">
                <h5>Planning de rotation par station</h5>

                <div class="btn-group" role="group" aria-label="navigation">
                    <button type="button" class="btn btn-white border" @click="goPrev" :disabled="isLoading || !canPrev">
                        <i class="ti ti-chevron-left me-1"></i>Semaines passées
                    </button>


                    <button type="button" class="btn btn-white border" @click="goNext" :disabled="isLoading || !canNext">
                        Semaines suivantes<i class="ti ti-chevron-right ms-1"></i>
                    </button>


                    <button type="button" class="btn btn-warning-light border" @click="duplicatePrevWeek" :disabled="isLoading || !canDuplicatePrev">
                        <i class="ti ti-copy me-1"></i>Régénérer le planning
                    </button>
                </div>


            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle text-center">
                            <thead class="table-dark">
                            <tr>
                                <th class="text-start">Agent</th>
                                <th v-for="d in days" :key="d.date">@{{ d.label }}</th>
                                <th style="width: 80px;">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-if="isLoading">
                                <td :colspan="days.length + 2" class="text-start text-muted p-3">
                                    Chargement...
                                </td>
                            </tr>

                            <tr v-else-if="stationGroups.length === 0">
                                <td :colspan="days.length + 2" class="text-start text-muted p-3">
                                    Aucun planning trouvé pour cette semaine.
                                </td>
                            </tr>

                            <template v-for="g in stationGroups">
                                <tr :key="'station-' + g.key" class="table-primary">
                                    <td :colspan="days.length + 2" class="text-uppercase fw-bold fs-5 text-start">
                                        <h5>@{{ g.station_name }}</h5>
                                    </td>
                                </tr>

                                <tr v-for="r in g.rows" :key="'row-' + g.key + '-' + r.agent.id">
                                    <td class="text-start">
                                        <strong class="me-2">@{{ r.agent.matricule }}</strong>
                                        @{{ r.agent.fullname }}
                                    </td>
                                    <td v-for="d in days" :key="d.date">
                                        <span v-if="r.days[d.date] && r.days[d.date].status === 'off'" class="badge bg-danger">OFF</span>
                                        <span v-else>@{{ (r.days[d.date] && r.days[d.date].label) ? r.days[d.date].label : '--' }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" @click="deleteAgentPlanning(r.agent)" title="Supprimer le planning de cet agent">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
        <!-- /Leads List -->

        <!-- Individual Agent Planning Modal -->
        <div class="modal fade" id="agentPlanningModal" tabindex="-1" aria-hidden="true" ref="modalEl">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-info-transparent">
                        <h5 class="modal-title">Planning Individuel de l'Agent</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">1. Sélectionner la Station</label>
                                <select class="form-select" v-model="modal.stationId" ref="modalStationSelect">
                                    <option value="">Choisir une station...</option>
                                    <option v-for="s in stations" :key="s.id" :value="s.id">@{{ s.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">2. Sélectionner l'Agent</label>
                                <select class="form-select" v-model="modal.agentId" ref="modalAgentSelect" :disabled="!modal.stationId">
                                    <option value="">Choisir un agent...</option>
                                    <option v-for="a in modal.agents" :key="a.id" :value="a.id">@{{ a.fullname }} (@{{ a.matricule }})</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="modal.agentId" class="border rounded p-3 bg-light">
                            <h6 class="mb-3 border-bottom pb-2">Configuration de la semaine : <strong>@{{ weekDate }}</strong></h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered bg-white text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th v-for="d in days" :key="d.date" style="min-width: 140px;">
                                                @{{ d.label }}<br>
                                                <small class="text-muted">@{{ d.date }}</small>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td v-for="d in days" :key="d.date" class="align-middle">
                                                <select class="form-select form-select-sm mb-2" v-model="modal.plannings[d.date]">
                                                    <option :value="null">OFF / REPOS</option>
                                                    <option v-for="h in modal.horaires" :key="h.id" :value="h.id">
                                                        @{{ h.libelle }}<br>
                                                        (@{{ h.started_at.substring(0,5) }}-@{{ h.ended_at.substring(0,5) }})
                                                    </option>
                                                </select>
                                                <button v-if="modal.plannings[d.date]" class="btn btn-xs btn-outline-danger w-100" @click="modal.plannings[d.date] = null">
                                                    <i class="ti ti-trash-x"></i> Vider
                                                </button>
                                                <span v-else class="badge bg-soft-danger text-danger">Repos</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else-if="modal.stationId" class="text-center py-4 text-muted">
                            <i class="ti ti-user-search fs-1 me-2"></i> Veuillez sélectionner un agent pour modifier son planning.
                        </div>
                        <div v-else class="text-center py-4 text-muted">
                            <i class="ti ti-building-community fs-1 me-2"></i> Veuillez d'abord sélectionner une station.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-white" data-bs-dismiss="modal">Fermer</button>
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
            function csrfToken() {
                const el = document.querySelector('meta[name="csrf-token"]');
                return el ? el.getAttribute('content') : '';
            }

            new Vue({
                el: '#planning-app',
                data: function () {
                    const today = new Date();
                    const yyyy = today.getFullYear();
                    const mm = String(today.getMonth() + 1).padStart(2, '0');
                    const dd = String(today.getDate()).padStart(2, '0');

                    return {
                        stations: [],
                        stationId: '',
                        weekDate: `${yyyy}-${mm}-${dd}`,

                        isLoading: false,
                        isUploading: false,
                        canPrev: false,
                        canNext: false,
                        canDuplicatePrev: false,

                        days: [
                            {date: 'lundi', label: 'Lundi'},
                            {date: 'mardi', label: 'Mardi'},
                            {date: 'mercredi', label: 'Mercredi'},
                            {date: 'jeudi', label: 'Jeudi'},
                            {date: 'vendredi', label: 'Vendredi'},
                            {date: 'samedi', label: 'Samedi'},
                            {date: 'dimanche', label: 'Dimanche'},
                        ],
                        stationGroups: [],

                        // Modal Data
                        modal: {
                            stationId: '',
                            agentId: '',
                            agents: [],
                            horaires: [],
                            plannings: {}, // date -> horaire_id
                            isSaving: false,
                            bsModal: null
                        }
                    };
                },
                watch: {
                    'modal.stationId': function(newVal) {
                        this.modal.agentId = '';
                        this.modal.agents = [];
                        this.modal.horaires = [];
                        if (newVal) {
                            this.loadModalAgents(newVal);
                            this.loadModalHoraires(newVal);
                        }
                        this.$nextTick(() => {
                            $(this.$refs.modalAgentSelect).val('').trigger('change');
                        });
                    },
                    'modal.agents': function() {
                        // Re-sync Select2 options when agents list changes
                        this.$nextTick(() => {
                            $(this.$refs.modalAgentSelect).trigger('change');
                        });
                    },
                    'modal.agentId': function(newVal) {
                        this.modal.plannings = {};
                        if (newVal) {
                            // Pre-fill with existing planning from current view if available
                            const existing = this.findExistingPlanningInView(newVal);
                            this.days.forEach(d => {
                                let hId = null;
                                if (existing && existing[d.date]) {
                                    hId = existing[d.date].horaire_id;
                                    if (existing[d.date].status === 'off') hId = null;
                                }
                                this.$set(this.modal.plannings, d.date, hId);
                            });
                        }
                    }
                },
                mounted: function () {
                    this.loadStations().then(() => this.fetchPlanning());
                    this.initSelect2();
                },
                methods: {
                    initSelect2() {
                        const self = this;
                        this.$nextTick(() => {
                            $(this.$refs.modalStationSelect).select2({ dropdownParent: $(this.$refs.modalEl) })
                                .on('change', function() { self.modal.stationId = $(this).val(); });

                            $(this.$refs.modalAgentSelect).select2({ dropdownParent: $(this.$refs.modalEl) })
                                .on('change', function() { self.modal.agentId = $(this).val(); });
                        });
                    },
                    openModal() {
                        if (!this.modal.bsModal) {
                            this.modal.bsModal = new bootstrap.Modal(this.$refs.modalEl);
                        }
                        this.modal.bsModal.show();
                    },
                    pickFile: function () {
                        if (this.$refs.fileInput) this.$refs.fileInput.click();
                    },
                    onFilePicked: function (e) {
                        const file = e && e.target ? e.target.files[0] : null;
                        if (!file) return;
                        this.uploadPlanning(file);
                        e.target.value = '';
                    },
                    loadStations: async function () {
                        try {
                            const res = await fetch('/stations/list', {credentials: 'same-origin'});
                            const json = await res.json();
                            this.stations = (json && json.sites) ? json.sites.map(s => ({id: s.id, name: s.name})) : [];
                        } catch (e) {
                            console.error(e);
                        }
                    },
                    async loadModalAgents(siteId) {
                        try {
                            // Appel au nouvel endpoint JSON dédié au lieu de /agents/data
                            const res = await fetch(`/rh/agents-for-station?station_id=${siteId}`, {credentials: 'same-origin'});
                            const json = await res.json();
                            this.modal.agents = json?.agents ?? [];
                        } catch (e) { this.modal.agents = []; }
                    },
                    async loadModalHoraires(siteId) {
                        try {
                            const res = await fetch(`/rh/horaires?site_id=${siteId}`, {credentials: 'same-origin'});
                            const json = await res.json();
                            this.modal.horaires = json?.horaires ?? [];
                        } catch (e) { this.modal.horaires = []; }
                    },
                    findExistingPlanningInView(agentId) {
                        for (let g of this.stationGroups) {
                            const row = g.rows.find(r => r.agent.id == agentId);
                            if (row) return row.days;
                        }
                        return null;
                    },
                    async saveAgentPlanning() {
                        if (!this.modal.agentId) return;

                        this.modal.isSaving = true;
                        try {
                            const payload = {
                                agent_id: this.modal.agentId,
                                start_date: this.days[0].date,
                                plannings: this.days.map(d => ({
                                    date: d.date,
                                    horaire_id: this.modal.plannings[d.date],
                                    is_rest_day: this.modal.plannings[d.date] === null
                                }))
                            };

                            const res = await fetch('/rh/planning/agent/update', {
                                method: 'POST',
                                body: JSON.stringify(payload),
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken(),
                                    'Content-Type': 'application/json'
                                },
                                credentials: 'same-origin'
                            });

                            if (!res.ok) throw new Error('Erreur lors de la sauvegarde');

                            Swal.fire({icon: 'success', title: 'Succès', text: 'Planning mis à jour.'});
                            this.modal.bsModal.hide();
                            await this.fetchPlanning();
                        } catch (e) {
                            Swal.fire({icon: 'error', title: 'Erreur', text: e.message});
                        } finally {
                            this.modal.isSaving = false;
                        }
                    },
                    async deleteAgentPlanning(agent) {
                        const result = await Swal.fire({
                            title: 'Confirmation',
                            text: `Voulez-vous supprimer tout le planning de la semaine pour ${agent.fullname} ?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'Oui, supprimer',
                            cancelButtonText: 'Annuler'
                        });

                        if (!result.isConfirmed) return;

                        this.isLoading = true;
                        try {
                            const res = await fetch('/rh/planning/agent/delete', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken(),
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    agent_id: agent.id,
                                    start_date: this.weekDate
                                }),
                                credentials: 'same-origin'
                            });

                            if (!res.ok) throw new Error('Erreur lors de la suppression');

                            Swal.fire({icon: 'success', title: 'Supprimé', text: 'Le planning a été effacé.'});
                            await this.fetchPlanning();
                        } catch (e) {
                            Swal.fire({icon: 'error', title: 'Erreur', text: e.message});
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    fetchPlanning: async function () {
                        this.isLoading = true;
                        try {
                            const qs = new URLSearchParams();
                            if (this.weekDate) qs.set('date', String(this.weekDate));
                            if (this.stationId) qs.set('station_id', String(this.stationId));

                            const url = `/rh/planning/week?${qs.toString()}`;
                            const res = await fetch(url, {credentials: 'same-origin'});
                            const json = await res.json();

                            this.days = (json && Array.isArray(json.days) ? json.days : []).map(d => ({date: d.date, label: d.label}));
                            this.stationGroups = (json && Array.isArray(json.stations)) ? json.stations : [];
                            await this.refreshNavAvailability();
                        } catch (e) {
                            console.error(e);
                            this.stationGroups = [];
                            this.canPrev = false;
                            this.canNext = false;
                            this.canDuplicatePrev = false;
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    addDaysIso: function (iso, deltaDays) {
                        const d = new Date(String(iso) + 'T00:00:00');
                        d.setDate(d.getDate() + deltaDays);
                        return d.toISOString().slice(0, 10);
                    },
                    weekExists: async function (isoDate) {
                        try {
                            const qs = new URLSearchParams();
                            qs.set('date', String(isoDate));
                            qs.set('exists_only', '1');
                            if (this.stationId) qs.set('station_id', String(this.stationId));

                            const url = `/rh/planning/week?${qs.toString()}`;
                            const res = await fetch(url, {credentials: 'same-origin'});
                            const json = await res.json();
                            return !!(json && json.exists);
                        } catch (e) {
                            return false;
                        }
                    },
                    refreshNavAvailability: async function () {
                        if (!this.weekDate) {
                            this.canPrev = false;
                            this.canNext = false;
                            this.canDuplicatePrev = false;
                            return;
                        }
                        const prevDate = this.addDaysIso(this.weekDate, -7);
                        const nextDate = this.addDaysIso(this.weekDate, 7);
                        const [prevOk, nextOk] = await Promise.all([
                            this.weekExists(prevDate),
                            this.weekExists(nextDate),
                        ]);
                        this.canPrev = prevOk;
                        this.canNext = nextOk;
                        this.canDuplicatePrev = prevOk; // Enabled only if prev week has planning
                    },
                    goPrev: async function () {
                        if (!this.canPrev) return;
                        this.weekDate = this.addDaysIso(this.weekDate, -7);
                        await this.fetchPlanning();
                    },
                    goNext: async function () {
                        if (!this.canNext) return;
                        this.weekDate = this.addDaysIso(this.weekDate, 7);
                        await this.fetchPlanning();
                    },
                    duplicatePrevWeek: async function () {
                        const result = await Swal.fire({
                            title: 'Confirmation',
                            text: 'Voulez-vous reconduire le planning de la semaine passée sur cette semaine ? Cela écrasera les données actuelles de cette semaine.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Oui, reconduire',
                            cancelButtonText: 'Annuler'
                        });

                        if (!result.isConfirmed) return;

                        this.isLoading = true;
                        try {
                            const res = await fetch('/rh/planning/duplicate-week', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken(),
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    station_id: this.stationId || null,
                                    current_week_date: this.weekDate
                                }),
                                credentials: 'same-origin'
                            });

                            const json = await res.json();
                            if (!res.ok) throw new Error(json.errors ? json.errors[0] : 'Erreur lors de la reconduction');

                            Swal.fire({icon: 'success', title: 'Succès', text: 'Le planning a été reconduit.'});
                            await this.fetchPlanning();
                        } catch (e) {
                            Swal.fire({icon: 'error', title: 'Erreur', text: e.message});
                        } finally {
                            this.isLoading = false;
                        }
                    },
                    uploadPlanning: async function (file) {
                        if (!this.weekDate) {
                            Swal.fire({icon: 'warning', title: 'Date requise', text: 'Choisis une date (semaine) a importer.'});
                            return;
                        }

                        this.isUploading = true;
                        try {
                            const fd = new FormData();
                            fd.append('file', file);
                            fd.append('start_date', this.weekDate);

                            const res = await fetch('/rh/planning/import-week', {
                                method: 'POST',
                                body: fd,
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken(),
                                },
                                credentials: 'same-origin',
                            });

                            const json = await res.json();
                            if (!res.ok) {
                                const msg = (json && json.errors && json.errors.length) ? json.errors.join('\\n') : 'Import failed';
                                Swal.fire({icon: 'error', title: 'Import', text: msg});
                                return;
                            }

                            Swal.fire({icon: 'success', title: 'Import', text: 'Planning importe. Rechargement...'});
                            await this.fetchPlanning();
                        } catch (e) {
                            console.error(e);
                            Swal.fire({icon: 'error', title: 'Import', text: 'Erreur pendant l import.'});
                        } finally {
                            this.isUploading = false;
                        }
                    },
                }
            });
        })();
    </script>
@endpush
