import { get, post, postJson, objectToFormData } from "../modules/http.js";
new Vue({
    el: "#App",

    data() {
        return {
            dashboardData: null,
            currentPage: 1,
            allSales: [],
            searchClient: "",
            searchDate: "",
            searchStatus: "",
            isDataLoading: false,
            selectedSalePayments: [],
            selectedClient: null,
            currentDetailsType: "",
            currentDetailsData: null,

            // Paiement
            paymentForm: {
                sale_id: null,
                phone_number: "",
                amount: "",
                default_amount: "",
                remaining_amount: 0,
                total_amount: 0,
                type: "", // 'initial' ou 'generate'
            },
            generatedPaymentUrl: "",
        };
    },

    mounted() {
        this.loadDashboardData();
    },

    methods: {
        loadDashboardData(page = 1) {
            this.isDataLoading = true;
            let url = `/api.dashboard?page=${page}`;
            if (this.searchClient) url += `&agent_search=${this.searchClient}`;
            if (this.searchDate) url += `&agent_date=${this.searchDate}`;
            if (this.searchStatus) url += `&agent_status=${this.searchStatus}`;
            get(url)
                .then(({ data }) => {
                    this.dashboardData = data;
                    this.currentPage = page;
                    if (data.agent_stats) {
                        this.allSales = data.agent_stats.sales;
                    }
                    this.renderChart();
                    this.isDataLoading = false;
                })
                .catch((err) => {
                    console.error("Erreur chargement dashboard:", err);
                    this.isDataLoading = false;
                });
        },

        renderChart() {
            if (!this.dashboardData || !$("#phone-by-agent").length) return;

            const chartData = this.dashboardData.chart_data;

            var sBar = {
                chart: {
                    height: 220,
                    type: "bar",
                    padding: {
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                    },
                    toolbar: {
                        show: false,
                    },
                },
                colors: ["#FF6F28"],
                grid: {
                    borderColor: "#E5E7EB",
                    strokeDashArray: 5,
                    padding: {
                        top: -20,
                        left: 0,
                        right: 0,
                        bottom: 0,
                    },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 5,
                        horizontal: true,
                        barHeight: "35%",
                        endingShape: "rounded",
                    },
                },
                dataLabels: {
                    enabled: false,
                },
                series: [
                    {
                        data: chartData.data,
                        name: "Téléphones",
                    },
                ],
                xaxis: {
                    categories: chartData.categories,
                    labels: {
                        style: {
                            colors: "#111827",
                            fontSize: "13px",
                        },
                    },
                },
            };

            // Détruire le chart existant si présent
            if (this.chart) {
                this.chart.destroy();
            }

            this.chart = new ApexCharts(
                document.querySelector("#phone-by-agent"),
                sBar,
            );

            this.chart.render();
        },

        nextPage() {
            if (
                this.dashboardData &&
                this.currentPage < this.dashboardData.phones_by_agent.last_page
            ) {
                this.loadDashboardData(this.currentPage + 1);
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.loadDashboardData(this.currentPage - 1);
            }
        },

        filterSales() {
            this.loadDashboardData(1);
        },

        saleStatus(status) {
            const statuses = {
                active: "Actif",
                completed: "Terminé",
                defaulted: "Annulé",
            };
            return statuses[status] || status;
        },

        viewClientDetails(client) {
            this.currentDetailsType = "client";
            this.currentDetailsData = client;
            $("#client_details_modal").modal("show");
        },

        viewSaleDetails(sale) {
            // Implémenter la vue des détails vente
            alert("Détails vente: " + sale.id);
        },

        cancelSale(sale) {
            if (confirm("Annuler cette vente ?")) {
                // Implémenter l'annulation
                alert("Vente annulée");
            }
        },

        activatePhone(phone, sale) {
            Swal.fire({
                title: "Activer le téléphone ?",
                text: "Cela enverra les commandes MDM pour bloquer le téléphone jusqu'au prochain paiement.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Oui, activer",
                cancelButtonText: "Annuler",
            }).then((result) => {
                if (result.isConfirmed) {
                    postJson(`/phones/${phone.id}/activate`, {
                        sale_id: sale.id,
                    })
                        .then(({ data, status }) => {
                            if (data.status === "success") {
                                Swal.fire({
                                    icon: "success",
                                    title: "Succès",
                                    text: "Téléphone activé et commandes MDM envoyées.",
                                });
                                this.loadDashboardData(this.currentPage); // Recharger la liste
                            } else {
                                Swal.fire({
                                    title: "Erreur",
                                    text:
                                        data.message ||
                                        "Une erreur est survenue",
                                });
                            }
                        })
                        .catch((error) => {
                            console.error(
                                "Erreur activation téléphone:",
                                error,
                            );
                            Swal.fire({
                                title: "Erreur",
                                text: "Impossible d'activer le téléphone",
                            });
                        });
                }
            });
        },

        viewPaymentHistory(sale) {
            this.selectedSalePayments = sale.payments || [];
            $("#paymentHistoryModal").modal("show");
        },

        /* ================= PAIEMENT ================= */
        openPayModal(sale, type) {
            this.paymentForm.sale_id = sale.id;
            this.paymentForm.phone_number = sale.client
                ? sale.client.main_phone
                : "";
            this.paymentForm.type = type;
            this.paymentForm.total_amount = sale.sale_price;

            // Logique de montant par défaut
            const defaultAmt =
                type === "initial"
                    ? sale.down_payment
                    : sale.installment_amount;

            this.paymentForm.default_amount = defaultAmt;
            this.paymentForm.amount = defaultAmt;
            this.paymentForm.remaining_amount = sale.remaining_amount;
            this.generatedPaymentUrl = "";
            $("#pay_modal").modal("show");
        },

        processPayment() {
            if (!this.paymentForm.phone_number) {
                Swal.fire(
                    "Erreur",
                    "Veuillez renseigner le numéro de téléphone.",
                    "error",
                );
                return;
            }

            const endpoint =
                this.paymentForm.type === "initial"
                    ? "/api/payments.initial"
                    : "/api/payments.generateURL";

            const params = {
                sale_id: this.paymentForm.sale_id,
                amount: this.paymentForm.amount,
            };

            if (this.paymentForm.type === "initial") {
                params.phone_number = this.paymentForm.phone_number;
            } else {
                params.phone = this.paymentForm.phone_number;
            }

            Swal.fire({
                title: "Traitement en cours...",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            postJson(endpoint, params)
                .then(({ data }) => {
                    Swal.close();
                    if (data.status === "success") {
                        if (this.paymentForm.type === "initial") {
                            Swal.fire(
                                "Succès",
                                "Demande de paiement lancée. Veuillez confirmer sur le téléphone.",
                                "success",
                            );
                            $("#pay_modal").modal("hide");
                            this.loadDashboardData(this.currentPage);
                        } else {
                            this.generatedPaymentUrl = data.url;
                        }
                    } else {
                        let errorMsg = "Une erreur est survenue.";
                        if (data.errors) {
                            errorMsg = Array.isArray(data.errors)
                                ? data.errors.join(", ")
                                : data.errors;
                        }
                        Swal.fire("Erreur", errorMsg, "error");
                    }
                })
                .catch((err) => {
                    Swal.close();
                    console.error(err);
                    Swal.fire(
                        "Erreur",
                        "Une erreur est survenue lors de la communication avec le serveur.",
                        "error",
                    );
                });
        },

        copyPaymentUrl() {
            const el = this.$refs.paymentUrlInput;
            el.select();
            document.execCommand("copy");
            Swal.fire({
                toast: true,
                position: "top-end",
                icon: "success",
                title: "Lien copié !",
                showConfirmButton: false,
                timer: 1500,
            });
        },

        sharePaymentUrl() {
            if (navigator.share) {
                navigator
                    .share({
                        title: "Lien de paiement",
                        text: "Voici votre lien pour effectuer le paiement de votre téléphone.",
                        url: this.generatedPaymentUrl,
                    })
                    .catch(console.error);
            } else {
                const text = encodeURIComponent(
                    "Voici votre lien pour effectuer le paiement de votre téléphone : " +
                        this.generatedPaymentUrl,
                );
                window.open(
                    `https://wa.me/${this.paymentForm.phone_number}?text=${text}`,
                    "_blank",
                );
            }
        },
    },

    computed: {
        saleFrequency() {
            return (status) => {
                const statuses = {
                    weekly: "Hebdomadaire",
                    monthly: "Mensuelle",
                    daily: "Journalière",
                };
                return statuses[status] || status;
            };
        },
    },
});
