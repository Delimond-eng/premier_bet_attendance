import { get } from "./http.js";

const SEARCH_URL = "/accounting/saisie/comptes/search";

/**
 * Select éditable avec recherche de comptes SYSCOHADA.
 * Clé : string ('journal_cp') ou number (index de ligne).
 */
export const compteSelectMixin = {
    data() {
        return {
            compteSelectUi: {},
            compteLabels: {},
        };
    },

    methods: {
        compteUiKey(key) {
            return String(key);
        },

        ensureCompteUi(key) {
            const k = this.compteUiKey(key);
            if (!this.compteSelectUi[k]) {
                this.$set(this.compteSelectUi, k, {
                    open: false,
                    loading: false,
                    results: [],
                    query: "",
                    editing: false,
                });
            }
            return this.compteSelectUi[k];
        },

        getCompteNumero(key) {
            if (key === "journal_cp") {
                return this.form?.compte_contrepartie || "";
            }
            if (key === "tiers_collectif") {
                return this.form?.num_compte_collectif || "";
            }
            if (key === "gl_compte" || key === "lettrage_compte") {
                return this.numCompte || "";
            }
            if (typeof key === "number" && this.lignes?.[key]) {
                return this.lignes[key].num_compte || "";
            }
            return "";
        },

        setCompteNumero(key, numCompte, libelle = null) {
            if (key === "journal_cp" && this.form) {
                this.form.compte_contrepartie = numCompte;
            } else if (key === "tiers_collectif" && this.form) {
                this.form.num_compte_collectif = numCompte;
            } else if (key === "gl_compte" || key === "lettrage_compte") {
                this.numCompte = numCompte;
            } else if (typeof key === "number" && this.lignes?.[key]) {
                this.lignes[key].num_compte = numCompte;
            }
            if (numCompte && libelle) {
                this.$set(this.compteLabels, numCompte, libelle);
            }
            const ui = this.ensureCompteUi(key);
            ui.editing = false;
            ui.query = "";
            ui.open = false;
        },

        compteDisplayText(key) {
            const ui = this.ensureCompteUi(key);
            if (ui.editing) {
                return ui.query;
            }
            const num = this.getCompteNumero(key);
            if (!num) {
                return "";
            }
            const lib = this.compteLabels[num];
            return lib ? `${num} — ${lib}` : num;
        },

        async searchComptes(query) {
            const { data } = await get(`${SEARCH_URL}?q=${encodeURIComponent(query || "")}`);
            return data.status === "success" ? data.comptes || [] : [];
        },

        async onCompteSearchInput(key, event) {
            const ui = this.ensureCompteUi(key);
            ui.editing = true;
            ui.query = event.target.value;
            ui.open = true;
            ui.loading = true;

            clearTimeout(ui._timer);
            ui._timer = setTimeout(async () => {
                try {
                    ui.results = await this.searchComptes(ui.query);
                } finally {
                    ui.loading = false;
                }
            }, 280);
        },

        onCompteSearchFocus(key) {
            const ui = this.ensureCompteUi(key);
            ui.open = true;
            if (!ui.results.length) {
                ui.loading = true;
                this.searchComptes(ui.query || this.getCompteNumero(key))
                    .then((list) => {
                        ui.results = list;
                    })
                    .finally(() => {
                        ui.loading = false;
                    });
            }
        },

        onCompteSearchBlur(key) {
            setTimeout(() => {
                const ui = this.ensureCompteUi(key);
                ui.open = false;
                if (ui.editing && ui.query.trim()) {
                    const raw = ui.query.split("—")[0].trim();
                    if (raw) {
                        this.setCompteNumero(key, raw);
                    }
                }
                ui.editing = false;
            }, 200);
        },

        selectCompteOption(key, compte) {
            this.setCompteNumero(key, compte.num_compte, compte.libelle);
            if (typeof key === "number" && this.lignes?.[key] && compte.est_compte_tiers) {
                // suggère un tiers si besoin — pas de changement auto
            }
        },

        async prefetchCompteLabel(numCompte) {
            if (!numCompte || this.compteLabels[numCompte]) {
                return;
            }
            const list = await this.searchComptes(numCompte);
            const found = list.find((c) => c.num_compte === numCompte);
            if (found) {
                this.$set(this.compteLabels, numCompte, found.libelle);
            }
        },

        async prefetchComptesFromLignes(lignes) {
            const nums = [...new Set((lignes || []).map((l) => l.num_compte).filter(Boolean))];
            await Promise.all(nums.map((n) => this.prefetchCompteLabel(n)));
        },

        compteUiOpen(key) {
            return this.ensureCompteUi(key).open;
        },

        compteUiLoading(key) {
            return this.ensureCompteUi(key).loading;
        },

        compteUiResults(key) {
            return this.ensureCompteUi(key).results;
        },
    },
};
