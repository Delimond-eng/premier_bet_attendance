import { get, post } from "../modules/http.js";
import { initSelect2ForVue } from "../modules/select2.js";

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            map: null,
            markers: {},
            stations: [],
            selectedStationId: "",
            activeMaintenances: [],
            activeMaintenanceId: null,
            knownMaintenanceIds: [],
            isFirstPolling: true,
            sidebar: null,
            currentStation: {},
            currentMaintenance: {}
        };
    },

    computed: {
        maintenanceMap() {
            const map = {};
            this.activeMaintenances.forEach(m => {
                map[Number(m.station_id)] = m;
            });
            return map;
        }
    },

    watch: {
        maintenanceMap: {
            handler(newMap) {
                this.syncMarkersUI(newMap);
            },
            deep: true
        },
        selectedStationId(newVal) {
            if (newVal) {
                this.zoomToStation(newVal);
            }
        }
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.initMap();
    },

    methods: {
        initMap() {
            try {
                // Centre Kinshasa par défaut
                this.map = L.map('map', {
                    center: [-4.325, 15.3222],
                    zoom: 13,
                    minZoom: 2,
                    maxZoom: 22
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; Salama Group LTD',
                    maxZoom: 19,
                    maxNativeZoom: 22
                }).addTo(this.map);

                setTimeout(() => {
                    this.map.invalidateSize();
                }, 500);

                // On charge les stations, puis on lance le polling
                this.loadStations().then(() => {
                    this.startPolling();
                });

                const sbEl = document.getElementById('maintenance-info-sidebar');
                if (sbEl) {
                    this.sidebar = new bootstrap.Offcanvas(sbEl);
                }
            } catch (e) {
                console.error("Erreur Leaflet:", e);
            }
        },

        startPolling() {
            this.loadActiveMaintenances().finally(() => {
                setTimeout(() => this.startPolling(), 10000);
            });
        },

        async loadStations() {
            try {
                this.isLoading = true;
                const response = await get("/stations/list");
                const resData = response.data;

                if (resData && resData.status === 'success' && resData.sites) {
                    let markersAdded = 0;
                    const stationsWithCoords = [];

                    resData.sites.forEach(station => {
                        const coords = this.parseLatLng(station.latlng);
                        if (coords) {
                            this.addStationMarker(station, coords);
                            stationsWithCoords.push({
                                id: station.id,
                                name: station.name
                            });
                            markersAdded++;
                        }
                    });

                    this.stations = stationsWithCoords.sort((a, b) =>
                        String(a.name || "").localeCompare(String(b.name || ""))
                    );
                    this.$nextTick(() => this.initStationZoomSelect());

                    if (markersAdded > 0) {
                        // Délai pour laisser Leaflet s'initialiser correctement avant le fitBounds
                        setTimeout(() => {
                            this.fitMarkers();
                            this.map.invalidateSize();
                        }, 600);
                    }
                }
            } catch (error) {
                console.error("Erreur loadStations:", error);
            } finally {
                this.isLoading = false;
            }
        },

        fitMarkers() {
            const markerArray = Object.values(this.markers).map(m => m.marker);
            if (markerArray.length === 0) {
                this.map.setView([-4.325, 15.3222], 13);
                return;
            }

            const group = new L.featureGroup(markerArray);
            this.map.fitBounds(group.getBounds(), {
                padding: [50, 50],
                maxZoom: 14 // Vue d'ensemble ville
            });
        },

        parseLatLng(latlng) {
            if (!latlng || String(latlng).trim() === "") return null;
            const cleanLatLng = String(latlng).replace(/[\(\) ]/g, '');
            const parts = cleanLatLng.split(',');
            if (parts.length !== 2) return null;

            const lat = parseFloat(parts[0]);
            const lng = parseFloat(parts[1]);
            if (isNaN(lat) || isNaN(lng)) return null;

            return [lat, lng];
        },

        getMaxMapZoom() {
            const z = Number(this.map?.getMaxZoom?.());
            return Number.isFinite(z) && z > 0 ? z : 19;
        },

        initStationZoomSelect() {
            initSelect2ForVue(this.$refs.stationZoomSelect, {
                placeholder: "Zoomer sur une station...",
                getValue: () => this.selectedStationId,
                setValue: (v) => {
                    this.selectedStationId = v;
                }
            });
        },

        zoomToStation(stationId, level = null) {
            const id = Number(stationId);
            if (!id || !this.map) return;

            const stationEntry = this.markers[id];
            if (!stationEntry?.marker) return;

            const latlng = stationEntry.marker.getLatLng();
            const targetZoom = level || this.getMaxMapZoom();
            this.map.flyTo(latlng, targetZoom, { duration: 1.0 });
        },

        addStationMarker(station, coords) {
            const icon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div class="marker-container" id="marker-station-${station.id}">
                        <div class="station-marker"></div>
                        <div class="station-label">${station.name}</div>
                      </div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            const marker = L.marker(coords, { icon: icon }).addTo(this.map);

            this.$set(this.markers, station.id, {
                marker: marker,
                station: station
            });

            marker.on('click', () => {
                const maintenance = this.maintenanceMap[Number(station.id)];
                if (maintenance) {
                    this.showSidebar(station, maintenance);
                    this.zoomToStation(station.id, 17);
                } else {
                    this.fitMarkers();
                }
            });
        },

        async loadActiveMaintenances() {
            try {
                const response = await get("/reports/maintenance/data?only_active=1&per_page=100");
                const resData = response.data;
                if (resData && resData.status === 'success') {
                    const maintenances = resData.maintenances.data;
                    const currentIds = maintenances.map(m => Number(m.id));

                    // Détection des maintenances à focaliser
                    const isNewMaintenance = !this.isFirstPolling && maintenances.filter(m => !this.knownMaintenanceIds.includes(Number(m.id))).length > 0;
                    const shouldFocusInitial = this.isFirstPolling && maintenances.length > 0;

                    if (isNewMaintenance || shouldFocusInitial) {
                        const target = maintenances[0]; // La plus récente
                        if (target.station_id && this.markers[Number(target.station_id)]) {
                            // Petit délai pour s'assurer que le fitMarkers de l'init est passé
                            setTimeout(() => {
                                this.zoomToStation(target.station_id, 17);
                                const stationData = this.markers[Number(target.station_id)].station;
                                this.showSidebar(stationData, target);
                            }, 1000);
                        }
                    }

                    this.activeMaintenances = maintenances;
                    this.knownMaintenanceIds = currentIds;
                    this.isFirstPolling = false;
                }
            } catch (error) {
                console.error("Erreur polling maintenances:", error);
            }
        },

        syncMarkersUI(maintenanceMap) {
            Object.keys(this.markers).forEach(stationId => {
                const markerElement = document.getElementById(`marker-station-${stationId}`);
                if (!markerElement) return;

                const dot = markerElement.querySelector('.station-marker');
                const label = markerElement.querySelector('.station-label');
                const isMaintenance = !!maintenanceMap[Number(stationId)];
                const hasPulse = !!markerElement.querySelector('.pulse-animation');

                if (isMaintenance) {
                    dot.classList.add('in-maintenance');
                    if (label) label.classList.add('in-maintenance');
                    if (!hasPulse) {
                        const pulse = document.createElement('div');
                        pulse.className = 'pulse-animation';
                        markerElement.appendChild(pulse);
                    }
                } else {
                    dot.classList.remove('in-maintenance');
                    if (label) label.classList.remove('in-maintenance');
                    if (hasPulse) {
                        const pulseEl = markerElement.querySelector('.pulse-animation');
                        if (pulseEl) pulseEl.remove();
                    }
                }
            });
        },

        showSidebar(station, maintenance) {
            this.currentStation = station;
            this.currentMaintenance = maintenance || {};
            this.activeMaintenanceId = maintenance ? maintenance.id : null;

            document.getElementById('sb-station-name').innerText = station.name;
            document.getElementById('sb-station-code').innerText = 'Code: ' + station.code;
            document.getElementById('sb-station-address').innerText = station.adresse || 'N/A';

            const photoInContainer = document.getElementById('sb-photo-in-container');
            const photoInImg = document.getElementById('sb-photo-in');

            if (maintenance && maintenance.agent) {
                document.getElementById('sb-agent-name').innerText = maintenance.agent.fullname;
                document.getElementById('sb-agent-matricule').innerText = 'Matricule: ' + maintenance.agent.matricule;
                document.getElementById('sb-agent-photo').src = maintenance.agent.photo || "/assets/img/profiles/avatar-01.jpg";
                document.getElementById('sb-maintenance-date').innerText = maintenance.date_maintenance;
                document.getElementById('sb-maintenance-start').innerText = maintenance.started_at;
                document.getElementById('sb-maintenance-active-ui').style.display = 'block';

                if (maintenance.photo_debut) {
                    photoInImg.src = maintenance.photo_debut;
                    photoInImg.setAttribute('data-zoom', maintenance.photo_debut);
                    photoInContainer.style.display = 'block';
                } else {
                    photoInContainer.style.display = 'none';
                }
            }

            if (this.sidebar) {
                this.sidebar.show();
            }
        },

        async closeActiveMaintenance() {
            if (!this.activeMaintenanceId) return;

            const res = await Swal.fire({
                title: "Confirmer la clôture",
                text: "Voulez-vous vraiment clôturer cette maintenance manuellement ?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Oui, clôturer",
                cancelButtonText: "Annuler",
                customClass: {
                    confirmButton: 'btn btn-danger',
                    cancelButton: 'btn btn-light'
                }
            });

            if (!res.isConfirmed) return;

            try {
                this.isLoading = true;
                const formData = new FormData();
                formData.append('key', 'maintenance-out');
                formData.append('matricule', this.currentMaintenance.agent.matricule);
                formData.append('station_id', this.currentStation.id);

                if (this.currentMaintenance.photo_debut) {
                    formData.append('photo', this.currentMaintenance.photo_debut);
                }

                const response = await fetch("/presences/store", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const resData = await response.json();

                if (resData.status === 'success') {
                    Swal.fire("Succès", "Maintenance clôturée avec succès.", "success");
                    if (this.sidebar) this.sidebar.hide();
                    await this.loadActiveMaintenances();
                } else {
                    Swal.fire("Erreur", (resData.errors ? resData.errors.join(', ') : 'Inconnue'), "error");
                }
            } catch (error) {
                console.error("Erreur clôture:", error);
                Swal.fire("Erreur", "Une erreur est survenue lors de la communication avec le serveur.", "error");
            } finally {
                this.isLoading = false;
            }
        }
    },
});

window.closeActiveMaintenance = function() {
    const app = document.getElementById('App').__vue__;
    if (app) app.closeActiveMaintenance();
};
