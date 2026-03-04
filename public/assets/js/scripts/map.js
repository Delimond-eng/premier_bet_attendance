import { get, post } from "../modules/http.js";

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            map: null,
            markers: {}, // Mapping station_id -> {marker, station}
            activeMaintenances: [], // Liste brute des maintenances actives (polling)
            activeMaintenanceId: null,
            sidebar: null,
            currentStation: {},
            currentMaintenance: {}
        };
    },

    computed: {
        /**
         * Mapping réactif : ID Station -> Objet Maintenance
         * Permet un accès instantané O(1) pour le watcher et les clics
         */
        maintenanceMap() {
            const map = {};
            this.activeMaintenances.forEach(m => {
                map[m.station_id] = m;
            });
            return map;
        }
    },

    watch: {
        /**
         * Watcher fluide : Se déclenche dès que la liste des maintenances change.
         * Il synchronise l'UI de chaque marqueur sans recréer les objets Leaflet.
         */
        maintenanceMap: {
            handler(newMap) {
                this.syncMarkersUI(newMap);
            },
            deep: true
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
            // Initialisation de la carte sur Kinshasa
            this.map = L.map('map').setView([-4.4419, 15.2663], 12);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.map);

            this.loadStations();

            // Polling récursif fluide (10s pour un feeling realtime sans surcharger)
            this.startPolling();

            // Initialisation de la sidebar Bootstrap
            const sbEl = document.getElementById('maintenance-info-sidebar');
            if (sbEl) {
                this.sidebar = new bootstrap.Offcanvas(sbEl);
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
                const data = await get("/stations/list");

                if (data.status === 'success') {
                    data.sites.forEach(station => {
                        if (station.latlng) {
                            const coords = station.latlng.split(',').map(c => parseFloat(c.trim()));
                            if (coords.length === 2 && !isNaN(coords[0]) && !isNaN(coords[1])) {
                                this.addStationMarker(station, coords);
                            }
                        }
                    });
                }
            } catch (error) {
                console.error("Erreur chargement stations:", error);
            } finally {
                this.isLoading = false;
            }
        },

        addStationMarker(station, coords) {
            const icon = L.divIcon({
                className: 'custom-div-icon',
                html: `<div class="marker-container" id="marker-station-${station.id}">
                        <div class="station-marker" style="background-color: #3388ff; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 4px rgba(0,0,0,0.3);"></div>
                        <div class="station-label" style="position: absolute; top: 18px; left: 50%; transform: translateX(-50%); background: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; white-space: nowrap; border: 1px solid #ccc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">${station.name}</div>
                      </div>`,
                iconSize: [14, 14],
                iconAnchor: [7, 7]
            });

            const marker = L.marker(coords, { icon: icon }).addTo(this.map);

            // Enregistrement réactif du marqueur
            this.$set(this.markers, station.id, {
                marker: marker,
                station: station
            });

            marker.on('click', () => {
                const maintenance = this.maintenanceMap[station.id];
                this.showSidebar(station, maintenance);
            });
        },

        async loadActiveMaintenances() {
            try {
                // On récupère uniquement les maintenances "In" (end_at est null)
                const data = await get("/reports/maintenance/data?only_active=1&per_page=100");
                if (data.status === 'success') {
                    this.activeMaintenances = data.maintenances.data;
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
                const isMaintenance = !!maintenanceMap[stationId];
                const hasPulse = !!markerElement.querySelector('.pulse-animation');

                if (isMaintenance) {
                    // Maintenance en cours (In)
                    dot.style.backgroundColor = '#ef4444';
                    dot.style.boxShadow = '0 0 8px #ef4444';

                    if (!hasPulse) {
                        const pulse = document.createElement('div');
                        pulse.className = 'pulse-animation';
                        pulse.style.cssText = "border-radius: 50%; height: 40px; width: 40px; position: absolute; left: -13px; top: -13px; background: rgba(239, 68, 68, 0.4); animation: pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;";
                        markerElement.appendChild(pulse);
                    }
                } else {
                    // Maintenance terminée (Out) ou aucune maintenance
                    dot.style.backgroundColor = '#3388ff';
                    dot.style.boxShadow = '0 0 4px rgba(0,0,0,0.3)';
                    if (hasPulse) {
                        markerElement.querySelector('.pulse-animation').remove();
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

                // Gestion de la photo de début (maintenance-in)
                if (maintenance.photo_debut) {
                    photoInImg.src = maintenance.photo_debut;
                    photoInImg.setAttribute('data-zoom', maintenance.photo_debut);
                    photoInContainer.style.display = 'block';
                } else {
                    photoInContainer.style.display = 'none';
                }
            } else {
                // Reset infos agent si pas de maintenance
                document.getElementById('sb-agent-name').innerText = "---";
                document.getElementById('sb-agent-matricule').innerText = "Matricule: ---";
                document.getElementById('sb-agent-photo').src = "/assets/img/profiles/avatar-01.jpg";
                document.getElementById('sb-maintenance-date').innerText = "---";
                document.getElementById('sb-maintenance-start').innerText = "---";
                document.getElementById('sb-maintenance-active-ui').style.display = 'none';
                photoInContainer.style.display = 'none';
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

                // Préparation des données via FormData
                const formData = new FormData();
                formData.append('key', 'maintenance-out');
                formData.append('matricule', this.currentMaintenance.agent.matricule);
                formData.append('station_id', this.currentStation.id);

                // Reprise de la photo IN pour le OUT via le champ 'photo' générique
                if (this.currentMaintenance.photo_debut) {
                    formData.append('photo', this.currentMaintenance.photo_debut);
                }

                // Utilisation de fetch pour envoyer le FormData avec le token CSRF
                const response = await fetch("/presences/store", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    Swal.fire("Succès", "Maintenance clôturée avec succès.", "success");
                    if (this.sidebar) this.sidebar.hide();
                    await this.loadActiveMaintenances();
                } else {
                    Swal.fire("Erreur", (data.errors ? data.errors.join(', ') : 'Inconnue'), "error");
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

// Exposer globalement pour le bouton onclick dans le HTML de la sidebar
window.closeActiveMaintenance = function() {
    const app = document.getElementById('App').__vue__;
    if (app) app.closeActiveMaintenance();
};
