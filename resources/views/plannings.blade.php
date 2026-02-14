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
                        Semaines passées
                    </button>
                    <button type="button" class="btn btn-white border" @click="goNext" :disabled="isLoading || !canNext">
                        Semaines suivantes
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
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-if="isLoading">
                                <td :colspan="days.length + 1" class="text-start text-muted p-3">
                                    Chargement...
                                </td>
                            </tr>

                            <tr v-else-if="stationGroups.length === 0">
                                <td :colspan="days.length + 1" class="text-start text-muted p-3">
                                    Aucun planning trouvé pour cette semaine.
                                </td>
                            </tr>

                            <template v-for="g in stationGroups">
                                <tr :key="'station-' + g.key" class="table-primary">
                                    <td :colspan="days.length + 1" class="text-uppercase fw-bold fs-5 text-start">
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
                                </tr>
                            </template>

                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
        <!-- /Leads List -->
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
                    };
                },
                mounted: function () {
                    this.loadStations().then(() => this.fetchPlanning());
                },
                methods: {
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
