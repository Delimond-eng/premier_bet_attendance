import { get, post } from "../modules/http.js";

new Vue({
    el: "#ManualAttendanceApp",

    data() {
        return {
            from: window.manualAttendanceDefaultRange.from,
            to: window.manualAttendanceDefaultRange.to,
            agentId: "",
            agent: null,
            agentsById: {},
            days: [],
            isLoading: false,
            error: "",
            success: "",
        };
    },

    mounted() {
        this.initRangePicker();
        this.loadAgents();
    },

    methods: {
        async loadAgents() {
            const $ = window.$;
            const select = $(this.$refs.agentSelect);
            select.prop("disabled", true);

            try {
                const { data, status } = await get("/rh/manual-attendance/agents");
                if (status < 200 || status >= 300) {
                    throw new Error(data?.message || "Impossible de charger la liste des agents.");
                }

                const agents = data?.results || [];
                this.agentsById = Object.fromEntries(agents.map((agent) => [String(agent.id), agent]));
                agents.forEach((agent) => {
                    select.append(new Option(agent.text, agent.id, false, false));
                });

                select.select2({
                    width: "100%",
                    placeholder: "Rechercher un agent...",
                    allowClear: true,
                });
                select.on("change.manualAttendance", () => {
                    this.agentId = select.val() || "";
                    this.agent = this.agentsById[String(this.agentId)] || null;
                    this.days = [];
                    this.error = "";
                    this.success = "";
                    if (this.agentId) this.loadMonth();
                });
            } catch (error) {
                this.error = error.message || "Impossible de charger la liste des agents.";
            } finally {
                select.prop("disabled", false);
            }
        },

        initRangePicker() {
            const $ = window.$;
            if (!$ || !$.fn || !$.fn.daterangepicker) return;

            const start = window.moment(this.from);
            const end = window.moment(this.to);
            $('#manual-range').daterangepicker({
                startDate: start,
                endDate: end,
                opens: 'left',
                locale: {
                    format: 'DD/MM/YYYY',
                    applyLabel: "Appliquer",
                    cancelLabel: "Annuler",
                    daysOfWeek: ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam"],
                    monthNames: ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"],
                    firstDay: 1,
                },
            }, (rangeStart, rangeEnd) => {
                this.from = rangeStart.format('YYYY-MM-DD');
                this.to = rangeEnd.format('YYYY-MM-DD');
                this.loadMonth();
            });

            $('#manual-range').val(`${start.format('DD/MM/YYYY')} - ${end.format('DD/MM/YYYY')}`);
        },

        async loadMonth() {
            if (!this.agentId || !this.from || !this.to) return;
            this.isLoading = true;
            this.error = "";
            this.success = "";
            try {
                const params = new URLSearchParams({ agent_id: this.agentId, from: this.from, to: this.to });
                const { data, status } = await get(`/rh/manual-attendance/month?${params.toString()}`);
                if (status < 200 || status >= 300) throw new Error(data?.message || "Impossible de charger les pointages.");
                this.agent = data.agent;
                this.days = (data.days || []).map((day) => ({
                    ...day,
                    input_start: day.started_at || day.default_started_at || "",
                    input_end: day.ended_at || day.default_ended_at || "",
                    saving: false,
                }));
            } catch (error) {
                this.days = [];
                this.error = error.message || "Une erreur est survenue lors du chargement.";
            } finally {
                this.isLoading = false;
            }
        },

        formatDate(value) {
            const [year, month, day] = value.split("-");
            return `${day}/${month}/${year}`;
        },

        canSave(day) {
            return day.has_entry ? Boolean(day.input_end) : Boolean(day.input_start);
        },

        async save(day) {
            day.saving = true;
            this.error = "";
            this.success = "";
            try {
                const { data, status } = await post("/rh/manual-attendance/store", {
                    agent_id: this.agentId,
                    date: day.date,
                    started_at: day.has_entry ? null : (day.input_start || null),
                    ended_at: day.input_end || null,
                });
                if (status < 200 || status >= 300 || data?.status !== "success") {
                    throw new Error(data?.message || data?.errors?.[0] || "Le pointage n’a pas pu être enregistré.");
                }
                this.success = data.message;
                await this.loadMonth();
            } catch (error) {
                this.error = error.message || "Une erreur est survenue lors de l’enregistrement.";
            } finally {
                day.saving = false;
            }
        },
    },
});
