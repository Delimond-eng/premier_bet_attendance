import { get } from "../../modules/http.js";
import { livresMixin } from "./livres-common.js";

new Vue({
    el: "#App",
    mixins: [livresMixin],
    data() {
        return {
            type: window.__LIVRES_TRESORERIE_TYPE__ || "banque",
            numCompte: "",
            comptesListe: [],
            synthese: [],
            data: null,
        };
    },

    computed: {
        totaux() {
            const lignes = this.data?.lignes || [];
            return {
                debit: lignes.reduce((s, l) => s + (Number(l.debit) || 0), 0),
                credit: lignes.reduce((s, l) => s + (Number(l.credit) || 0), 0),
            };
        },
    },

    methods: {
        queryParams(extra = {}) {
            return livresMixin.methods.queryParams.call(this, {
                num_compte: this.numCompte?.trim() || "",
                type: this.type,
                ...extra,
            });
        },

        async initPage() {
            await this.loadComptes();
            const saved = sessionStorage.getItem(`livres_${this.type}_compte`);
            if (saved) {
                this.numCompte = saved;
                await this.loadData();
            } else if (this.comptesListe.length) {
                this.numCompte = this.comptesListe[0].num_compte;
                await this.loadData();
            }
        },

        async loadComptes() {
            const { data } = await get(`/accounting/livres/tresorerie/comptes?type=${this.type}`);
            if (data.status === "success") {
                this.comptesListe = data.comptes || [];
            }
        },

        selectCompte(num) {
            this.numCompte = num;
            sessionStorage.setItem(`livres_${this.type}_compte`, num);
            this.loadData();
        },

        async loadData() {
            if (!this.numCompte?.trim()) {
                this.error = ["Sélectionnez un compte de trésorerie."];
                return;
            }
            sessionStorage.setItem(`livres_${this.type}_compte`, this.numCompte);
            this.isLoading = true;
            try {
                const qs = this.queryParams({
                    num_compte: this.numCompte.trim(),
                    type: this.type,
                });
                const { data } = await get(`/accounting/livres/tresorerie/data?${qs}`);
                if (!this.handleResponse(data)) {
                    return;
                }
                this.data = data.data;
                this.synthese = data.synthese || [];
            } finally {
                this.isLoading = false;
            }
        },
    },
});
