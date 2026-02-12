import { get, post, postJson, objectToFormData } from "../modules/http.js";
new Vue({
    el: "#App",

    data() {
        return {
            currentTarget: null,
            stream: null,

            sales: [],
            isDataLoading: false,

            searchAgent: "",
            searchClient: "",
            searchDate: "",
            searchStatus: "",

            currentDetailsType: null,
            currentDetailsData: null,

            // Paiement
            paymentForm: {
                sale_id: null,
                phone_number: "",
                amount: "",
                default_amount: "",
                remaining_amount: 0,
                type: "", // 'initial' ou 'generate'
            },
            generatedPaymentUrl: "",

            form: {
                // CLIENT
                first_name: "",
                last_name: "",
                gender: "",
                date_of_birth: "",

                main_phone: "",
                alternative_phone: "",
                language: "",
                profession: "",
                address: "",

                id_type: "",
                id_number: "",

                id_photo: "", // base64 ou File
                client_photo: "", // base64 ou File

                emergency_contact_name: "",
                emergency_contact_phone: "",

                signature_client: "", // base64
                signature_agent: "", // base64

                // VENTE
                sale: {
                    imei: "",
                    down_payment: "",
                    payment_frequency: "",
                },

                phone: null,
            },
        };
    },

    mounted() {
        if (this.$refs.clientPad) this.initSignature(this.$refs.clientPad);
        if (this.$refs.agentPad) this.initSignature(this.$refs.agentPad);

        // Charger les ventes
        this.loadSales();
    },

    methods: {
        /* ================= CAMERA ================= */
        openCamera(target) {
            this.currentTarget = target;

            // Toujours stopper avant
            this.stopCamera();

            $("#cameraModal").modal("show");

            this.$nextTick(async () => {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: "environment" },
                        audio: false,
                    });

                    if (this.$refs.video) {
                        this.$refs.video.srcObject = this.stream;
                        this.$refs.video.play();
                    }
                } catch (e) {
                    console.warn(e);
                    alert("Accès caméra refusé");
                }
            });
        },

        capturePhoto() {
            if (!this.$refs.video || !this.$refs.canvas) {
                console.warn("Video ou canvas non disponible");
                return;
            }

            const video = this.$refs.video;
            const canvas = this.$refs.canvas;

            canvas.width = video.videoWidth || 640;
            canvas.height = video.videoHeight || 480;

            const ctx = canvas.getContext("2d");
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            this.form[this.currentTarget] = canvas.toDataURL("image/jpeg", 0.9);

            this.stopCamera();
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach((track) => track.stop());
                this.stream = null;
            }

            if (this.$refs.video) {
                this.$refs.video.srcObject = null;
            }

            $("#cameraModal").modal("hide");
        },

        /* ================= SIGNATURE ================= */

        initSignature(canvas) {
            const ctx = canvas.getContext("2d");
            let drawing = false;

            const getPos = (e) => {
                const rect = canvas.getBoundingClientRect();

                if (e.touches) {
                    return {
                        x: e.touches[0].clientX - rect.left,
                        y: e.touches[0].clientY - rect.top,
                    };
                }

                return {
                    x: e.offsetX,
                    y: e.offsetY,
                };
            };

            const start = (e) => {
                e.preventDefault();
                drawing = true;
                const pos = getPos(e);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            };

            const move = (e) => {
                if (!drawing) return;
                e.preventDefault();
                const pos = getPos(e);
                ctx.lineWidth = 2;
                ctx.lineCap = "round";
                ctx.strokeStyle = "#000";
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
            };

            const end = () => {
                drawing = false;
                ctx.beginPath();
            };

            // Souris
            canvas.addEventListener("mousedown", start);
            canvas.addEventListener("mousemove", move);
            canvas.addEventListener("mouseup", end);
            canvas.addEventListener("mouseleave", end);

            // Tactile
            canvas.addEventListener("touchstart", start, { passive: false });
            canvas.addEventListener("touchmove", move, { passive: false });
            canvas.addEventListener("touchend", end);
        },

        clearPad(type) {
            const canvas =
                type === "client" ? this.$refs.clientPad : this.$refs.agentPad;

            if (!canvas) return;

            canvas
                .getContext("2d")
                .clearRect(0, 0, canvas.width, canvas.height);
        },

        getPhoneEmei(event) {
            get(`/phones/imei/${this.form.sale.imei}`).then(
                ({ data, status }) => {
                    console.log(JSON.stringify(data));
                    if (data.status === "success") {
                        this.form.phone = data.phone;
                        this.form.sale.down_payment =
                            data.phone.model.min_down_payment;
                    } else {
                        this.form.phone = null;
                    }
                },
            );
        },

        loadSales() {
            this.isDataLoading = true;

            // Construction de l'URL avec les filtres pour le backend
            let query = [];
            if (this.searchDate) {
                query.push(`date=${this.searchDate}`);
            }
            if (this.searchStatus) {
                query.push(`status=${this.searchStatus}`);
            }

            const url = "/sales" + (query.length ? "?" + query.join("&") : "");

            get(url)
                .then(({ data, status }) => {
                    this.isDataLoading = false;
                    this.sales = data.sales || [];
                })
                .catch((err) => {
                    this.isDataLoading = false;
                    console.error("Erreur lors du chargement des ventes:", err);
                });
        },

        filterSales() {
            // On déclenche un rechargement depuis le backend
            this.loadSales();
        },

        viewAgentDetails(agent) {
            get(`/agents/${agent.id}`)
                .then(({ data, status }) => {
                    if (data.status === "success") {
                        this.currentDetailsType = "agent";
                        this.currentDetailsData = data.data;
                        this.currentDetailsData.photo = null;
                        $("#details_modal").modal("show");
                    }
                })
                .catch((err) => {
                    Swal.fire({
                        title: "Erreur",
                        text: "Impossible de charger les détails de l'agent.",
                    });
                });
        },

        viewClientDetails(client) {
            get(`/clients/${client.id}`)
                .then(({ data, status }) => {
                    if (data.status === "success") {
                        this.currentDetailsType = "client";
                        this.currentDetailsData = data.client;
                        this.currentDetailsData.photo =
                            data.client.client_photo;
                        $("#details_modal").modal("show");
                    }
                })
                .catch((err) => {
                    Swal.fire({
                        title: "Erreur",
                        text: "Impossible de charger les détails du client.",
                    });
                });
        },

        cancelSale(sale) {
            Swal.fire({
                title: "Annuler la vente ?",
                text: "Cette action est irréversible et remettra le téléphone en stock.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Oui, annuler",
                cancelButtonText: "Non",
            }).then((result) => {
                if (result.isConfirmed) {
                    postJson(`/sales/${sale.id}/cancel`)
                        .then(({ data, status }) => {
                            if (data.status === "success") {
                                Swal.fire({
                                    icon: "success",
                                    title: "Succès",
                                    text: data.message,
                                });
                                this.loadSales(); // Recharger la liste
                            } else {
                                Swal.fire({
                                    title: "Erreur",
                                    text: data.message,
                                });
                            }
                        })
                        .catch((err) => {
                            Swal.fire({
                                title: "Erreur",
                                text: "Une erreur est survenue lors de l'annulation.",
                            });
                        });
                }
            });
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
                                this.loadSales(); // Recharger la liste
                            } else {
                                Swal.fire({
                                    title: "Erreur",
                                    text: data.message,
                                });
                            }
                        })
                        .catch((err) => {
                            Swal.fire({
                                title: "Erreur",
                                text: "Une erreur est survenue lors de l'activation.",
                            });
                        });
                }
            });
        },

        saleStatus(status) {
            const statuses = {
                active: "Actif",
                completed: "Terminé",
                defaulted: "Annulé",
            };
            return statuses[status] || status;
        },

        /* ================= PAIEMENT ================= */
        openPayModal(sale, type) {
            this.paymentForm.sale_id = sale.id;
            this.paymentForm.phone_number = sale.client
                ? sale.client.main_phone
                : "";

            // Logique de montant par défaut
            const defaultAmt = type === 'initial' ? sale.down_payment : sale.installment_amount;

            this.paymentForm.default_amount = defaultAmt;
            this.paymentForm.amount = defaultAmt;
            this.paymentForm.remaining_amount = sale.remaining_amount;
            this.paymentForm.type = type;
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

            // Adaptation des paramètres selon l'endpoint
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
                            this.loadSales();
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

        /* ================= SUBMIT ================= */

        submitForm() {
            // Validation côté frontend
            const requiredFields = [
                { field: "first_name", label: "Prénom" },
                { field: "last_name", label: "Nom" },
                { field: "gender", label: "Genre" },
                { field: "date_of_birth", label: "Date de naissance" },
                { field: "main_phone", label: "Téléphone principal" },
                { field: "language", label: "Langue" },
                { field: "id_type", label: "Type de pièce" },
                { field: "id_number", label: "Numéro de pièce" },
                { field: "id_photo", label: "Photo de la pièce" },
                { field: "client_photo", label: "Photo du client" },
                {
                    field: "emergency_contact_name",
                    label: "Nom contact d'urgence",
                },
                {
                    field: "emergency_contact_phone",
                    label: "Téléphone contact d'urgence",
                },
                { field: "sale.imei", label: "IMEI du téléphone" },
                { field: "sale.down_payment", label: "Acompte" },
                {
                    field: "sale.payment_frequency",
                    label: "Fréquence de paiement",
                },
            ];

            const missingFields = requiredFields.filter(({ field }) => {
                const value = field.includes(".")
                    ? this.getNestedValue(this.form, field)
                    : this.form[field];
                return !value || value.toString().trim() === "";
            });

            if (missingFields.length > 0) {
                Swal.fire({
                    icon: "warning",
                    title: "Champs requis manquants",
                    text: `Veuillez remplir: ${missingFields
                        .map((f) => f.label)
                        .join(", ")}`,
                });
                return;
            }

            // 🖊️ signatures
            if (this.$refs.clientPad) {
                this.form.signature_client = this.$refs.clientPad.toDataURL();
            }

            if (this.$refs.agentPad) {
                this.form.signature_agent = this.$refs.agentPad.toDataURL();
            }

            // Vérifier que les signatures sont présentes
            if (!this.form.signature_client || !this.form.signature_agent) {
                Swal.fire({
                    icon: "warning",
                    title: "Signatures requises",
                    text: "Veuillez signer le formulaire (client et agent).",
                });
                return;
            }

            // 🔁 Transformation en FormData
            const formData = objectToFormData(this.form);

            // Afficher loader
            Swal.fire({
                title: "Enregistrement en cours...",
                text: "Veuillez patienter",
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                },
            });

            post("/sales", formData)
                .then(({ data, status }) => {
                    Swal.close();

                    if (data.errors) {
                        Swal.fire({
                            title: "Erreur de validation",
                            text: Array.isArray(data.errors)
                                ? data.errors.join("\n")
                                : Object.values(data.errors).flat().join("\n"),
                        });
                        return;
                    }

                    if (data.status === "success" && data.message) {
                        Swal.fire({
                            icon: "success",
                            title: "Succès",
                            text: data.message,
                            confirmButtonText: "OK",
                        }).then(() => {
                            // Redirection ou rechargement
                            window.location.href = "/sales.view";
                        });
                    }
                })
                .catch((err) => {
                    Swal.close();
                    console.error("Erreur lors de la soumission:", err);

                    let errorMessage = "Une erreur inattendue s'est produite.";

                    if (err.response) {
                        if (
                            err.response.status === 422 &&
                            err.response.data.errors
                        ) {
                            errorMessage = Array.isArray(
                                err.response.data.errors,
                            )
                                ? err.response.data.errors.join("\n")
                                : Object.values(err.response.data.errors)
                                      .flat()
                                      .join("\n");
                        } else if (err.response.data.message) {
                            errorMessage = err.response.data.message;
                        }
                    }

                    Swal.fire({
                        title: "Erreur",
                        text: errorMessage,
                    });
                });
        },

        // Helper pour accéder aux valeurs imbriquées
        getNestedValue(obj, path) {
            return path
                .split(".")
                .reduce((current, key) => current && current[key], obj);
        },
    },

    computed: {
        allSales() {
            if (!this.sales) return [];
            return this.sales.filter((sale) => {
                const agentName = sale.agent ? `${sale.agent.first_name} ${sale.agent.last_name}`.toLowerCase() : "";
                const agentMatch = !this.searchAgent || agentName.includes(this.searchAgent.toLowerCase());

                const clientName = sale.client ? `${sale.client.first_name} ${sale.client.last_name}`.toLowerCase() : "";
                const clientMatch = !this.searchClient || clientName.includes(this.searchClient.toLowerCase());

                // Le filtrage par date et status est désormais géré au chargement via le backend
                return agentMatch && clientMatch;
            });
        },
        saleFrequency() {
            return (frequency) => {
                const frequencies = {
                    weekly: "Hebdomadaire",
                    daily: "Journalière",
                    monthly: "Mensuelle",
                };
                return frequencies[frequency] || frequency;
            };
        },
    },
});
