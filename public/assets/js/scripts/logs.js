import { get } from "../modules/http.js";

new Vue({
    el: "#App",
    data() {
        return {
            isLoading: false,
            users: []
        };
    },
    mounted() {
        if (document.getElementById("global-loader")) {
            document.getElementById("global-loader").style.display = "none";
        }
        this.loadLogs();
    },
    methods: {
        async loadLogs() {
            this.isLoading = true;
            try {
                const { data } = await get("/users/all");
                // On trie par dernier accès (décroissant)
                this.users = (data.users || []).sort((a, b) => {
                    if (!a.last_seen_at) return 1;
                    if (!b.last_seen_at) return -1;
                    return new Date(b.last_seen_at) - new Date(a.last_seen_at);
                });
            } catch (e) {
                console.error("Erreur chargement logs", e);
            } finally {
                this.isLoading = false;
            }
        },
        formatDate(date) {
            if (!date) return "Jamais connecté";
            return moment(date).format("DD/MM/YYYY HH:mm:ss");
        },
        getTimeAgo(date) {
            if (!date) return "";
            return moment(date).fromNow();
        }
    }
});
