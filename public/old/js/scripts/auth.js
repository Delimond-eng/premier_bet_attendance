import { post } from "../modules/http.js";

new Vue({
    el: "#auth-app",
    data() {
        return {
            loading: false,
            passwordVisible: false,
            form: {
                email: "",
                password: "",
                remember: false,
            },
        };
    },
    methods: {
        async handleLogin() {
            this.loading = true;
            try {
                const response = await post("/login", this.form);
                const { data, status } = response;

                const getErrorMessage = () => {
                    if (!data) {
                        return "Identifiants invalides";
                    }
                    if (typeof data.errors === "string") {
                        return data.errors;
                    }
                    if (Array.isArray(data.errors)) {
                        return data.errors.join(" ");
                    }
                    if (typeof data.errors === "object") {
                        return Object.values(data.errors).flat().join(" ");
                    }
                    return data.message || "Identifiants invalides";
                };

                if (status === 200 || status === 201) {
                    if (data.errors !== undefined) {
                        Swal.fire({
                            icon: "error",
                            title: "Erreur",
                            text: getErrorMessage(),
                        });
                        return;
                    }

                    if (data.result !== undefined) {
                        Swal.fire({
                            icon: "success",
                            title: "Connexion réussie",
                            text: "Vous allez être redirigé...",
                            timer: 1500,
                            showConfirmButton: false,
                        }).then(() => {
                            window.location.href = data.result.redirect;
                        });
                        return;
                    }
                }

                Swal.fire({
                    icon: "error",
                    title: "Erreur",
                    text: getErrorMessage(),
                });
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: "error",
                    title: "Erreur système",
                    text: "Une erreur est survenue lors de la connexion. Veuillez réessayer.",
                });
            } finally {
                this.loading = false;
            }
        },
    },
});
