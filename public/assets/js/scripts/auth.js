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
                remember: false
            }
        };
    },
    methods: {
        async handleLogin() {
            this.loading = true;
            try {
                const response = await post('/login', this.form);
                const { data, status } = response;


                console.log(JSON.stringify(data))

                if (status === 200 || status === 201) {

                    if(data.errors !== undefined){
                        Swal.fire({
                            icon: 'error',
                            title: 'Erreur',
                            text: data.errors.toString() || 'Identifiants invalides'
                        });
                        return;
                    }
                    else if(data.result !== undefined){
                        Swal.fire({
                            icon: 'success',
                            title: 'Connexion réussie',
                            text: 'Vous allez être redirigé...',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = data.result.redirect;
                        });
                    }


                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: data.message || 'Identifiants invalides'
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur système',
                    text: 'Une erreur est survenue lors de la connexion. Veuillez réessayer.'
                });
            } finally {
                this.loading = false;
            }
        }
    }
});
