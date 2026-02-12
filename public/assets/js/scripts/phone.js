import { get, post, postJson } from "../modules/http.js";
new Vue({
    el: "#App",
    data() {
        return {
            error: null,
            result: null,
            isLoading: false,
            isDataLoading: false,
            search: "",
            models: [], // Liste des modèles (utilisée par enroll et models)
            phones: [], // Liste des téléphones (utilisée par index)
            formModel: {
                brand: "",
                model: "",
                price: "",
                min_down_payment: "",
                model_id: "",
            },
            formPhone: {
                phone_model_id: "",
                imei: "",
            },
            currentPhone: null,
            qrcode_url: null,
            timer: null,
        };
    },

    mounted() {
        if ($("#loader").length) {
            document.getElementById("global-loader").style.display = "none";
        }

        const path = location.pathname;
        if (path === "/models") {
            this.viewAllModels();
        } else if (path === "/phones.view") {
            this.viewAllPhones();
        } else if (path === "/phones.enroll") {
            this.viewAllModels();
        }
    },

    methods: {
        viewAllModels() {
            this.isDataLoading = true;
            get("/phones/models")
                .then(({ data }) => {
                    this.isDataLoading = false;
                    this.models = data.models;
                })
                .catch(() => {
                    this.isDataLoading = false;
                });
        },

        createModel() {
            this.isLoading = true;
            postJson("/phones/models", this.formModel)
                .then(({ data }) => {
                    this.isLoading = false;
                    if (data.errors) {
                        this.error = data.errors;
                        return;
                    }
                    if (data.status === "success") {
                        this.error = null;
                        this.reset();
                        this.viewAllModels();
                        $("#model-create").modal("hide");
                        Swal.fire("Succès", "Modèle enregistré", "success");
                    }
                })
                .catch(console.error);
        },

        editModel(data) {
            this.formModel.model_id = data.id;
            this.formModel.brand = data.brand;
            this.formModel.model = data.model;
            this.formModel.price = data.price;
            this.formModel.min_down_payment = data.min_down_payment;
            $("#model-create").modal("show");
        },

        viewAllPhones() {
            this.isDataLoading = true;
            get("/phones")
                .then(({ data }) => {
                    this.isDataLoading = false;
                    this.phones = data.phones;
                })
                .catch(() => {
                    this.isDataLoading = false;
                });
        },

        createPhone() {
            this.isLoading = true;
            postJson("/phones", this.formPhone)
                .then(({ data }) => {
                    this.isLoading = false;
                    if (data.errors) {
                        Swal.fire(
                            "Erreur",
                            Array.isArray(data.errors)
                                ? data.errors.join(", ")
                                : data.errors,
                            "error",
                        );
                        return;
                    }
                    this.currentPhone = data.phone;
                    // On utilise le qrcode_base64 pour un affichage garanti et immédiat
                    this.qrcode_url = data.qrcode_base64;
                    this.checkEnrollStatus();
                })
                .catch((err) => {
                    this.isLoading = false;
                    console.error(err);
                });
        },

        checkEnrollStatus() {
            if (!this.currentPhone) return;
            this.timer = setInterval(() => {
                get(`/phones/enroll.status/${this.currentPhone.id}`)
                    .then(({ data }) => {
                        if (
                            data.mdm_status === "pre_enrolled" ||
                            data.mdm_status === "active"
                        ) {
                            this.formPhone.imei = data.imei;
                            clearInterval(this.timer);
                            Swal.fire({
                                icon: "success",
                                title: "Téléphone détecté !",
                                text:
                                    "L'IMEI " +
                                    data.imei +
                                    " a été enrôlé avec succès.",
                                timer: 3000,
                            });
                        }
                    })
                    .catch(console.error);
            }, 3000);
        },

        deletePhone(phone) {
            Swal.fire({
                title: "Supprimer le téléphone ?",
                text: "Cette action est irréversible.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Oui, supprimer",
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Suppression en cours...",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });
                    postJson(`/phones/${phone.id}/delete`).then(({ data }) => {
                        Swal.close();
                        if (data.status === "success") {
                            Swal.fire("Supprimé !", data.message, "success");
                            this.viewAllPhones();
                        } else {
                            Swal.fire("Erreur", data.message, "error");
                        }
                    });
                }
            });
        },

        showPhoneDetails(phone) {
            this.currentPhone = phone;
            $("#phone_details_modal").modal("show");
        },

        lockPhone(phone) {
            Swal.fire({
                title: "Verrouiller le téléphone ?",
                text: "Cela bloquera l'appareil immédiatement.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                confirmButtonText: "Oui, verrouiller",
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Verrouillage en cours...",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });
                    postJson(`/phones/${phone.id}/lock`).then(({ data }) => {
                        Swal.close();
                        if (data.status === "success") {
                            Swal.fire(
                                "Verrouillé !",
                                "Commande envoyée.",
                                "success",
                            );
                            this.viewAllPhones();
                        } else {
                            Swal.fire(
                                "Erreur",
                                data.errors || data.message,
                                "error",
                            );
                        }
                    });
                }
            });
        },

        unlockPhone(phone) {
            Swal.fire({
                title: "Déverrouiller le téléphone ?",
                text: "Cela débloquera l'appareil.",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#28a745",
                confirmButtonText: "Oui, déverrouiller",
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: "Déverrouillage en cours...",
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        },
                    });
                    postJson(`/phones/${phone.id}/unlock`).then(({ data }) => {
                        Swal.close();
                        if (data.status === "success") {
                            Swal.fire(
                                "Déverrouillé !",
                                "Commande envoyée.",
                                "success",
                            );
                            this.viewAllPhones();
                        } else {
                            Swal.fire(
                                "Erreur",
                                data.errors || data.message,
                                "error",
                            );
                        }
                    });
                }
            });
        },

        ringPhone(phone) {
            Swal.fire({
                title: "Faire sonner ?",
                text: "Le téléphone va sonner à plein volume.",
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "Oui, faire sonner",
            }).then((result) => {
                if (result.isConfirmed) {
                    postJson(`/phones/${phone.id}/ring`).then(({ data }) => {
                        if (data.status === "success") {
                            Swal.fire("Succès", data.message, "success");
                        } else {
                            Swal.fire(
                                "Erreur",
                                data.errors || data.message,
                                "error",
                            );
                        }
                    });
                }
            });
        },
        refreshDashboard(phone) {
            Swal.fire({
                title: "Actualisation dashboard client...",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });
            postJson(`/phones/${phone.id}/dashboard`)
                .then(({ data }) => {
                    Swal.close();
                    if (data.status === "success") {
                        Swal.fire("Succès", data.message, "success");
                    }
                })
                .catch((err) => {
                    Swal.close();
                });
        },

        locatePhone(phone) {
            const url = `/phones/${phone.id}/locate.view`;
            window.open(
                url,
                "_blank",
                "width=1100,height=800,menubar=no,toolbar=no,location=no,status=no",
            );
        },

        updateMdmApp() {
            Swal.fire({
                title: "Mise à jour globale",
                text: "Entrez l'URL directe du nouvel APK pour TOUS les appareils :",
                input: "text",
                inputPlaceholder: "https://...",
                showCancelButton: true,
                confirmButtonText: "Lancer UPDATE",
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    Swal.fire({
                        title: "Envoi en cours...",
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading(),
                    });

                    postJson(`/phones/update-global`, {
                        app_url: result.value,
                    }).then(({ data }) => {
                        Swal.close();
                        if (data.status === "success") {
                            Swal.fire("Succès !", data.message, "success");
                        } else {
                            Swal.fire(
                                "Erreur",
                                data.errors || data.message,
                                "error",
                            );
                        }
                    });
                }
            });
        },

        reset() {
            if (this.timer) clearInterval(this.timer);
            this.currentPhone = null;
            this.qrcode_url = null;
            this.formPhone = { phone_model_id: "", imei: "" };
            this.formModel = {
                brand: "",
                model: "",
                price: "",
                min_down_payment: "",
                model_id: "",
            };
        },

        refresh() {
            window.location.reload();
        },
    },

    computed: {
        allModels() {
            return this.models;
        },
        allPhones() {
            return this.phones;
        },
        currentModel() {
            if (!this.formPhone.phone_model_id) return null;
            return this.models.find(
                (m) => m.id == this.formPhone.phone_model_id,
            );
        },
    },

    destroyed() {
        if (this.timer) clearInterval(this.timer);
    },
});
