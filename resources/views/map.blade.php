@extends("layouts.app")

@section("content")
    <div class="content" id="App">
        <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
            <div class="my-auto mb-2">
                <h2 class="mb-1">Cartographie des stations</h2>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="/"><i class="ti ti-smart-home"></i></a>
                        </li>
                        <li class="breadcrumb-item">Stations</li>
                        <li class="breadcrumb-item active" aria-current="page">Cartographie</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-1">Localisation & Suivi Live</h5>
                    <p class="text-muted mb-0">Supervisez en temps réel le déroulement des interventions de maintenance sur l'ensemble des stations.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-fill" style="width: 320px;">
                        <select class="form-select" v-model="selectedStationId" ref="stationZoomSelect">
                            <option value="">Zoomer sur une station...</option>
                            <option v-for="s in stations" :key="s.id" :value="String(s.id)">@{{ s.name }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="map" style="height: 700px; width: 100%; border-radius: 0px;"></div>
            </div>
        </div>

        <!-- Sidebar for Station/Agent Info -->
        <div class="sidebar-themesettings offcanvas offcanvas-end" id="maintenance-info-sidebar" tabindex="-1">
            <div class="offcanvas-header d-flex align-items-center justify-content-between bg-dark">
                <div>
                    <h3 class="mb-1 text-white" id="sb-station-name">Détails Station</h3>
                    <p class="text-light mb-0" id="sb-station-code">Code: ---</p>
                </div>
                <a href="#" class="custom-btn-close d-flex align-items-center justify-content-center" data-bs-dismiss="offcanvas">
                    <i class="ti ti-x"></i>
                </a>
            </div>
            <div class="offcanvas-body">
                <div class="mb-4 text-center">
                    <img id="sb-agent-photo" src="{{ asset('assets/img/profiles/avatar-01.jpg') }}" alt="Agent" class="rounded-circle border border-2 border-primary mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                    <h4 id="sb-agent-name" class="mb-0">---</h4>
                    <p id="sb-agent-matricule" class="text-muted small">Matricule: ---</p>
                </div>

                <div class="card bg-light border-0 mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ti ti-calendar-event text-primary me-2 fs-18"></i>
                            <div>
                                <p class="mb-0 text-muted small">Date Maintenance</p>
                                <h6 id="sb-maintenance-date" class="mb-0">---</h6>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="ti ti-clock text-primary me-2 fs-18"></i>
                            <div>
                                <p class="mb-0 text-muted small">Débuté à</p>
                                <h6 id="sb-maintenance-start" class="mb-0">---</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 text-center" id="sb-photo-in-container" style="display:none;">
                    <p class="mb-1 text-muted small">Photo de début</p>
                    <img id="sb-photo-in" src="" alt="Photo début" class="img-fluid rounded border shadow-sm" style="max-height: 200px; cursor: zoom-in;" data-zoom="">
                </div>

                <div class="mb-3">
                    <p class="mb-1 text-muted small">Adresse Station</p>
                    <p id="sb-station-address" class="fw-medium">---</p>
                </div>

                <div id="sb-maintenance-active-ui" class="mt-auto">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <span class="spinner-grow spinner-grow-sm me-2 text-info" role="status"></span>
                        <div class="small">Maintenance en cours...</div>
                    </div>
                    <button type="button" class="btn btn-danger w-100 py-2 d-flex align-items-center justify-content-center" onclick="closeActiveMaintenance()">
                        <i class="ti ti-power me-2"></i> Clôturer Maintenance
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Conteneur du marqueur */
        .marker-container {
            position: relative;
            text-align: center;
        }

        /* Le point physique sur la carte */
        .station-marker {
            width: 16px;
            height: 16px;
            background-color: #3388ff;
            border: 2px solid #ffffff;
            border-radius: 50%;
            box-shadow: 0 0 4px rgba(0,0,0,0.4);
            transition: all 0.3s ease;
            z-index: 10;
        }

        /* État maintenance */
        .station-marker.in-maintenance {
            background-color: #ef4444;
            box-shadow: 0 0 10px #ef4444;
            transform: scale(1.2);
        }

        /* Label texte de la station */
        .station-label {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(51, 136, 255, 0.95); /* Couleur bleue par défaut */
            border: 1px solid #3388ff;
            border-radius: 4px;
            padding: 2px 8px;
            font-weight: 700;
            font-size: 11px;
            color: #ffffff;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        /* Label en maintenance */
        .station-label.in-maintenance {
            background: rgba(239, 68, 68, 0.95); /* Couleur rouge */
            border-color: #ef4444;
        }

        /* Animation Pulse */
        .pulse-animation {
            border-radius: 50%;
            height: 44px;
            width: 44px;
            position: absolute;
            left: -14px;
            top: -14px;
            background: rgba(239, 68, 68, 0.4);
            animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
            z-index: 5;
        }

        @keyframes pulse-ring {
            0% { transform: scale(.33); opacity: 1; }
            80%, 100% { transform: scale(1.2); opacity: 0; }
        }

        .custom-div-icon {
            background: none;
            border: none;
        }
    </style>
@endsection

@push("scripts")
    <script src="{{ asset('assets/js/scripts/map.js') }}" type="module"></script>
@endpush

