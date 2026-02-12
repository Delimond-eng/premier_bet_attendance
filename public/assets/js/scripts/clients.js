import { get, post, postJson, objectToFormData } from "../modules/http.js";
new Vue({
    el: "#App",

    data() {
        return {
            clients: [],
            filteredClients: [],
            isDataLoading: false,

            searchName: "",
            searchPhone: "",

            currentDetailsData: null,
        };
    },

    mounted() {
        this.loadClients();
    },

    computed: {
        allClients() {
            return this.filteredClients.length > 0
                ? this.filteredClients
                : this.clients;
        },
    },

    methods: {
        loadClients() {
            this.isDataLoading = true;
            get("/clients")
                .then(({ data, status }) => {
                    if (data.status === "success") {
                        this.clients = data.clients.data;
                        this.filteredClients = [];
                    }
                })
                .catch((err) => {
                    console.error("Erreur chargement clients:", err);
                })
                .finally(() => {
                    this.isDataLoading = false;
                });
        },

        filterClients() {
            if (!this.searchName && !this.searchPhone) {
                this.filteredClients = [];
                return;
            }

            this.filteredClients = this.clients.filter((client) => {
                const nameMatch =
                    !this.searchName ||
                    `${client.first_name} ${client.last_name}`
                        .toLowerCase()
                        .includes(this.searchName.toLowerCase());

                const phoneMatch =
                    !this.searchPhone ||
                    client.main_phone.includes(this.searchPhone) ||
                    (client.alternative_phone &&
                        client.alternative_phone.includes(this.searchPhone));

                return nameMatch && phoneMatch;
            });
        },

        viewClientDetails(client) {
            this.currentDetailsData = client;
            $("#details_modal").modal("show");
        },
    },
});
