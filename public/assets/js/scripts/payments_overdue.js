import { get, post, postJson, objectToFormData } from "../modules/http.js";
new Vue({
    el: "#App",

    data() {
        return {
            overduePayments: [],
            isDataLoading: false,
        };
    },

    mounted() {
        this.loadOverduePayments();
    },

    methods: {
        loadOverduePayments() {
            this.isDataLoading = true;
            get("/api/overdue-payments")
                .then(({ data }) => {
                    this.overduePayments = data.overdue_payments.data || [];
                })
                .catch((err) => {
                    console.error(
                        "Erreur chargement paiements en retard:",
                        err,
                    );
                })
                .finally(() => {
                    this.isDataLoading = false;
                });
        },

        daysOverdue(dueDate) {
            const due = new Date(dueDate);
            const now = new Date();
            const diffTime = now - due;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays > 0 ? diffDays : 0;
        },
    },
});
