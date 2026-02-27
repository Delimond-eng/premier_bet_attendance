import { get } from "../modules/http.js";

new Vue({
    el: "#App",

    data() {
        return {
            isLoading: false,
            range: {
                from: null,
                to: null,
                mode: "today",
            },
            maintenances: {
                summary: {
                    total: 0,
                    completed: 0,
                    ongoing: 0,
                    on_station: 0,
                    off_station: 0,
                },
                latest: [],
            },
            selectedMaintenance: null,
            _modal: null,
        };
    },

    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }

        const today = new Date().toISOString().slice(0, 10);
        this.range.mode = "today";
        this.range.from = today;
        this.range.to = today;

        this.initRangePicker();
        this.applyMode();
    },

    methods: {
        applyMode() {
            const m = window.moment;
            const now = m ? m() : null;

            if (this.range.mode === "today") {
                const d = m ? now.format("YYYY-MM-DD") : new Date().toISOString().slice(0, 10);
                this.range.from = d;
                this.range.to = d;
            }

            if (this.range.mode === "week") {
                if (m) {
                    this.range.from = now.clone().startOf("isoWeek").format("YYYY-MM-DD");
                    this.range.to = now.clone().endOf("isoWeek").format("YYYY-MM-DD");
                }
            }

            if (this.range.mode === "month") {
                if (m) {
                    this.range.from = now.clone().startOf("month").format("YYYY-MM-DD");
                    this.range.to = now.clone().endOf("month").format("YYYY-MM-DD");
                }
            }

            this.refresh();
        },

        initRangePicker() {
            const input = window.$?.(".bookingrange");
            if (!input || !input.length || !window.$?.fn?.daterangepicker || !window.moment) {
                return;
            }

            const start = window.moment();
            const end = window.moment();

            this.range.from = start.format("YYYY-MM-DD");
            this.range.to = end.format("YYYY-MM-DD");

            input.daterangepicker(
                {
                    startDate: start,
                    endDate: end,
                    locale: {
                        format: "DD/MM/YYYY",
                        applyLabel: "Appliquer",
                        cancelLabel: "Annuler",
                    },
                },
                (startDate, endDate) => {
                    this.range.mode = "custom";
                    this.range.from = startDate.format("YYYY-MM-DD");
                    this.range.to = endDate.format("YYYY-MM-DD");
                    this.refresh();
                }
            );
        },

        async refresh() {
            this.isLoading = true;

            const params = new URLSearchParams();
            if (this.range.from) params.set("from", this.range.from);
            if (this.range.to) params.set("to", this.range.to);

            try {
                const { data } = await get(`/dashboard/stats?${params.toString()}`);
                this.maintenances = {
                    ...this.maintenances,
                    ...(data?.maintenances ?? {}),
                };
            } catch (_) {
                this.maintenances = {
                    summary: {
                        total: 0,
                        completed: 0,
                        ongoing: 0,
                        on_station: 0,
                        off_station: 0,
                    },
                    latest: [],
                };
            } finally {
                this.isLoading = false;
            }
        },

        openDetails(item) {
            this.selectedMaintenance = item;
            if (!window.bootstrap?.Modal) return;

            if (!this._modal) {
                const el = document.getElementById("maintenanceDetailsModal");
                if (!el) return;
                this._modal = new window.bootstrap.Modal(el);
            }

            this._modal.show();
        },
    },
});
