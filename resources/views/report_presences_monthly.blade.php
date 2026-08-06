@extends("layouts.app")

@section("content")
    <div class="content" id="App" v-cloak>
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Rapport des presences (mensuel)</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Rapports</li>
                        <li class="breadcrumb-item active" aria-current="page">Mensuel</li>
                    </ol>
                </nav>
            </div>

            <div class="dropdown">
                <a href="javascript:void(0);" class="dropdown-toggle btn btn-white d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="ti ti-file-export me-1"></i>Exporter
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-3">
                    <li>
                        <a class="dropdown-item rounded-1" :href="exportExcelUrl" target="_blank">Exporter en Excel</a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-1" :href="exportPdfUrl" target="_blank">Exporter en PDF</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5>Synthese agents</h5>
                    <div class="d-flex align-items-center gap-2 flex-wrap">

                        <!-- Month/Year Selects -->
                        <select class="form-select" v-model.number="filters.month" style="width: 140px;" v-show="!useRange" @change="load">
                            <option v-for="m in monthOptions" :key="m.value" :value="m.value">@{{ m.label }}</option>
                        </select>
                        <select class="form-select" v-model.number="filters.year" style="width: 100px;" v-show="!useRange" @change="load">
                            <option v-for="y in yearOptions" :key="y" :value="y">@{{ y }}</option>
                        </select>

                        <!-- Date Range Input (replaces month/year selects) -->
                        <div v-show="useRange">
                            <div class="input-icon position-relative" style="width: 260px;">
                                <span class="input-icon-addon">
                                    <i class="ti ti-calendar text-gray-9"></i>
                                </span>
                                <input type="text" class="form-control date-range bookingrange" id="reportRange" placeholder="dd/mm/yyyy - dd/mm/yyyy">
                            </div>
                        </div>

                        <!-- Toggle Range -->
                        <div class="form-check form-switch me-2">
                            <input class="form-check-input" type="checkbox" id="useRange" v-model="useRange">
                            <label class="form-check-label fs-12" for="useRange">Intervalle</label>
                        </div>

                        @if(Auth::user()->matricule_prefix === null)
                        <select v-if="show_matricule_filter" class="form-select" v-model="filters.matricule_prefix" style="width: 160px;" @change="load">
                            <option value="">Sous-traitance</option>
                            <option v-for="p in prefixes" :key="p" :value="p">@{{ p }}</option>
                        </select>
                        @endif

                        <div style="width: 240px;">
                            <select class="form-select" v-model="filters.station_id" ref="stationSelect">
                                <option value="">Toutes les stations</option>
                                <option v-for="s in sites" :key="s.id" :value="s.id">@{{ s.name }}</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" @click="load" :disabled="isLoading">@{{ isLoading ? '...' : 'Charger' }}</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-3 gap-2">
                    <ul class="nav nav-tabs nav-tabs-solid mb-0">
                        <li class="nav-item">
                            <a href="javascript:void(0);" class="nav-link" :class="{ active: activeTab === 'brut' }" @click="switchTab('brut')">
                                Presences mensuelles brutes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="javascript:void(0);" class="nav-link" :class="{ active: activeTab === 'details' }" @click="switchTab('details')">
                                Presences mensuelles details
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="table-responsive" v-show="activeTab === 'brut'">
                    <table class="table" ref="tableRaw">
                        <thead class="thead-light">
                        <tr>
                            <th>Agent</th>
                            <th>Fonction</th>
                            <th>Station</th>
                            <th>Present</th>
                            <th>Retard</th>
                            <th>Absent</th>
                            <th>AN</th>
                            <th>Conge</th>
                            <th>Autorisation</th>
                            <th>TOT CM</th>
                            <th>TOT M</th>
                            <th>TOT CC</th>
                            <th>TOT CA</th>
                            <th>TOT Autres</th>
                            <th>Justif retard</th>
                            <th>Justif absence</th>
                            <th>Retard Cumulé</th>
                            <th>Heures Sup</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="r in rows" :key="r.agent_key">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="avatar avatar-sm me-2">
                                        <img v-if="r.agent?.photo" :src="r.agent?.photo" :data-zoom="r.agent?.photo" class="rounded-circle" alt="img">
                                        <img v-else src="{{asset("assets/img/avatar.jpg")}}" class="rounded-circle" alt="img">
                                    </span>
                                    <div>
                                        <h6 class="mb-0">@{{ r.agent?.fullname ?? '-' }}</h6>
                                        <small class="text-muted">@{{ r.agent?.matricule ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>@{{ r.agent?.fonction ?? '-' }}</td>
                            <td><span class="badge badge-lg badge-purple">@{{ r.agent?.station_name ?? '-' }}</span></td>
                            <td>@{{ r.present }}</td>
                            <td>@{{ r.retard }}</td>
                            <td>@{{ r.absent }}</td>
                            <td><span class="text-danger fw-bold">@{{ r.an }}</span></td>
                            <td>@{{ r.conge }}</td>
                            <td>@{{ r.autorisation }}</td>
                            <td>@{{ r.total_cm }}</td>
                            <td>@{{ r.total_m }}</td>
                            <td>@{{ r.total_cc }}</td>
                            <td>@{{ r.total_ca }}</td>
                            <td>@{{ r.total_other_leave_types }}</td>
                            <td>@{{ r.retard_justifie }}</td>
                            <td>@{{ r.absence_justifiee }}</td>
                            <td><span class="badge bg-danger-subtle text-danger">@{{ r.late_display }}</span></td>
                            <td><span class="badge bg-warning text-dark">@{{ r.overtime_display }}</span></td>
                            <td><span class="badge badge-info ms-2">Total preste : @{{ r.total_preste }}</span></td>
                        </tr>
                        <tr class="table-active fw-bold" v-if="rows.length">
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>@{{ summaryTotals.present }}</td>
                            <td>@{{ summaryTotals.retard }}</td>
                            <td>@{{ summaryTotals.absent }}</td>
                            <td>@{{ summaryTotals.an }}</td>
                            <td>@{{ summaryTotals.conge }}</td>
                            <td>@{{ summaryTotals.autorisation }}</td>
                            <td>@{{ summaryTotals.total_cm }}</td>
                            <td>@{{ summaryTotals.total_m }}</td>
                            <td>@{{ summaryTotals.total_cc }}</td>
                            <td>@{{ summaryTotals.total_ca }}</td>
                            <td>@{{ summaryTotals.total_other_leave_types }}</td>
                            <td>@{{ summaryTotals.retard_justifie }}</td>
                            <td>@{{ summaryTotals.absence_justifiee }}</td>
                            <td>-</td>
                            <td>-</td>
                            <td>@{{ summaryTotals.total_preste }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div class="table-responsive" v-show="activeTab === 'details'">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge text-bg-success">1 = Presence</span>
                        <span class="badge text-bg-success">2 = Double shift</span>
                        <span class="badge text-bg-success">2 C-1 = Double shift avec retard</span>
                        <span class="badge text-bg-info">1-R = Retard</span>
                        <span class="badge text-bg-danger">A = Absence</span>
                        <span class="badge bg-danger-subtle text-danger border">AN = Oubli sortie</span>
                        <span class="badge text-bg-secondary">OFF = Repos</span>
                        <span class="badge text-bg-success">CA = Congé Annuel</span>
                        <span class="badge text-bg-info">CC = Congé Circonstance</span>
                        <span class="badge text-white" style="background-color: #6f42c1;">CM = Congé Maternité</span>
                        <span class="badge text-bg-warning text-dark">M = Maladie</span>
                        <span class="badge text-bg-dark">AS = Autorisation spéciale</span>
                    </div>
                    <table class="table table-bordered table-sm align-middle attendance-details-table" ref="tableDetails">
                        <thead class="thead-light">
                        <tr>
                            <th>Matricule</th>
                            <th>Nom complet agent & Fonction</th>
                            <th>Station</th>
                            <th v-for="d in dynamicDayKeys" :key="'head-' + d" class="text-center attendance-day-head" v-html="formatDayHeader(d)"></th>
                            <th>Total</th>
                            <th>Tot presences</th>
                            <th>Tot absences</th>
                            <th>Tot AN</th>
                            <th>Tot retard</th>
                            <th>Retard Cumulé</th>
                            <th>Tot autorisation</th>
                            <th>Tot congé</th>
                            <th>TOT CM</th>
                            <th>TOT M</th>
                            <th>TOT CC</th>
                            <th>TOT CA</th>
                            <th>TOT Autres</th>
                            <th>Heures Sup</th>
                            <th>Tot OFF</th>
                            <th>Tot autres</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="r in detailedRows" :key="'d-' + r.agent_key">
                            <td>@{{ r.agent?.matricule ?? '' }}</td>
                            <td>
                                <div>
                                    <h6 class="mb-0">@{{ r.agent?.fullname ?? '-' }}</h6>
                                    <small class="text-info">@{{ r.agent?.fonction ?? '' }}</small>
                                </div>
                            </td>
                            <td><span class="badge badge-lg badge-purple">@{{ r.agent?.station_name ?? '-' }}</span></td>
                            <td
                                v-for="d in dynamicDayKeys"
                                :key="'cell-' + r.agent_key + '-' + d"
                                class="text-center attendance-day-cell"
                            >
                                <span
                                    class="badge attendance-day-badge"
                                    :class="r.day_classes[d]"
                                    :title="r.day_titles[d] || 'Statut'"
                                    :data-bs-content="r.day_details[d] || 'Aucune information'"
                                    data-bs-toggle="popover"
                                    data-bs-trigger="hover focus"
                                    data-bs-html="true"
                                    data-bs-placement="top"
                                    tabindex="0"
                                >@{{ r.day_codes[d] }}</span>
                            </td>
                            <td class="fw-semibold">@{{ r.total_count }}</td>
                            <td>@{{ r.total_presences }}</td>
                            <td>@{{ r.total_absences }}</td>
                            <td class="text-danger fw-bold">@{{ r.total_an }}</td>
                            <td>@{{ r.total_retards }}</td>
                            <td><span class="badge bg-danger-subtle text-danger">@{{ r.late_display }}</span></td>
                            <td>@{{ r.total_autorisations }}</td>
                            <td>@{{ r.total_conges }}</td>
                            <td>@{{ r.total_cm }}</td>
                            <td>@{{ r.total_m }}</td>
                            <td>@{{ r.total_cc }}</td>
                            <td>@{{ r.total_ca }}</td>
                            <td>@{{ r.total_other_leave_types }}</td>
                            <td><span class="badge bg-warning text-dark">@{{ r.overtime_display }}</span></td>
                            <td>@{{ r.total_off }}</td>
                            <td>@{{ r.total_others }}</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push("styles")
    <style>
        .attendance-details-table th.attendance-day-head,
        .attendance-details-table td.attendance-day-cell {
            min-width: 22px;
            width: 22px;
            max-width: 22px;
            padding: 0 !important;
            text-align: center;
            line-height: 1.0;
            height: 30px;
        }

        .attendance-day-badge {
            cursor: pointer !important;
            display: flex !important;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            font-size: 9px;
            font-weight: 700;
            border-radius: 0 !important;
            margin: 0 !important;
        }

        .attendance-details-table th.attendance-day-head {
            font-size: 11px;
            padding: .2rem .1rem !important;
        }

        .attendance-popover-header {
            font-weight: 700;
            font-size: 12px;
            color: #0f172a;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }

        .attendance-popover-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 11px;
            color: #334155;
            line-height: 1.5;
            white-space: nowrap;
        }

        .attendance-popover-key {
            font-weight: 600;
            color: #475569;
        }

        .attendance-popover-value {
            text-align: right;
            color: #0f172a;
        }

        .dp-day {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0;
            line-height: 1;
        }

        .dp-weekday {
            font-size: 9px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .dp-daynum {
            font-size: 11px;
            font-weight: 800;
            margin-top: 0px;
        }

        /* Dark popover theme for attendance details */
        .popover.popover-dark {
            --bs-popover-bg: #111827;
            --bs-popover-border-color: #374151;
            --bs-popover-color: #f8fafc;
            box-shadow: 0 4px 12px rgba(2,6,23,0.6);
        }

        .popover.popover-dark .popover-header {
            background: #0b1220;
            color: #f8fafc;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .popover.popover-dark .popover-body {
            background: transparent;
            color: #f8fafc;
        }
    </style>
@endpush

@push("scripts")
    <script type="module">
        import "{{ asset("assets/js/scripts/report-presences-monthly.js") . '?v=' . filemtime(public_path('assets/js/scripts/report-presences-monthly.js')) }}";

        document.addEventListener("DOMContentLoaded", function () {
            if (window.bootstrap && typeof window.bootstrap.Popover === "function") {
                const initPopovers = () => {
                    const elements = document.querySelectorAll('.attendance-day-badge[data-bs-toggle="popover"]:not(.popover-initialized)');
                    elements.forEach((el) => {
                        el.classList.add('popover-initialized');
                        new bootstrap.Popover(el, {
                            html: true,
                            trigger: 'hover focus',
                            placement: 'top',
                            sanitize: false,
                            container: 'body',
                            template: '<div class="popover popover-dark" role="tooltip"><div class="popover-arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
                        });
                    });
                };

                const observer = new MutationObserver((mutations) => {
                    initPopovers();
                });

                const appEl = document.getElementById('App');
                if (appEl) {
                    observer.observe(appEl, { childList: true, subtree: true });
                }

                initPopovers();
                $(document).on('draw.dt', function () {
                    initPopovers();
                });
            }
        });
    </script>
@endpush
