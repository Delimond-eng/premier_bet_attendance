/**
 * Mixin export PDF / Excel / CSV — définir exportBase sur la page Vue.
 * Ex. exportBase: '/accounting/export/livres/balance'
 */
export const exportMixin = {
    methods: {
        exportUrl(format, extra = {}) {
            const base = this.exportBase || "";
            if (!base) {
                return "#";
            }
            let qs = "";
            if (typeof this.queryParams === "function") {
                qs = this.queryParams(extra);
            } else if (typeof this.buildExportParams === "function") {
                qs = this.buildExportParams(extra);
            } else {
                qs = new URLSearchParams(extra).toString();
            }
            return `${base}/${format}${qs ? `?${qs}` : ""}`;
        },
    },
};
