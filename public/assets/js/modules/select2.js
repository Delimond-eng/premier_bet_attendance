export function initSelect2ForVue(selectEl, { getValue, setValue, placeholder }) {
    const $ = window.$;
    if (!selectEl || !$ || !$.fn || !$.fn.select2) return;

    const $el = $(selectEl);

    // Avoid double init when Vue rerenders.
    if ($el.hasClass("select2-hidden-accessible")) {
        $el.select2("destroy");
    }

    $el.select2({
        width: "resolve",
        placeholder: placeholder ?? "",
        allowClear: true,
    });

    // Sync initial value from Vue -> select2.
    $el.val(getValue?.() ?? "").trigger("change.select2");

    // Ensure Vue state reflects the current DOM value too (select2 can mutate it).
    const initialDomValue = $el.val();
    setValue?.(initialDomValue == null ? "" : String(initialDomValue));

    // Sync changes from select2 -> Vue.
    $el.off("change.select2Vue").on("change.select2Vue", () => {
        const v = $el.val();
        setValue?.(v == null ? "" : String(v));
    });
}
