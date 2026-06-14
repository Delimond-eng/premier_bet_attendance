/**
 * Évite l'affichage des moustaches Vue (@{{ }}) au chargement / refresh.
 */
export const vuePageMixin = {
    data() {
        return {
            pageReady: false,
        };
    },

    methods: {
        async bootPage(initFn) {
            const splash = document.getElementById("vue-splash-loader");
            try {
                if (typeof initFn === "function") {
                    await initFn.call(this);
                }
            } catch (e) {
                console.error(e);
                this.error = this.error || ["Erreur de chargement de la page."];
            } finally {
                this.pageReady = true;
                if (splash) {
                    splash.style.display = "none";
                }
                const globalLoader = document.getElementById("global-loader");
                if (globalLoader) {
                    globalLoader.style.display = "none";
                }
            }
        },
    },
};
