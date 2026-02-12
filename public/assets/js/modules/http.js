/***
 * Fait passer les requêtes HTTP en GET ou en POST,
 * si data est défini c'est la requête POST qui sera lancée,
 * autrement c'est la requête GET qui sera lancée
 * @param {String} [url=null]
 * @param {Object} form
 * @returns {data, status} data: http response if status equal 200 or 201
 */
export async function post(url, body, customHeaders = {}) {
    try {
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        const headers = {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
            ...customHeaders,
        };

        if (!(body instanceof FormData)) {
            headers["Content-Type"] = "application/json";
            body = JSON.stringify(body);
        }

        const response = await fetch(url, {
            method: "POST",
            headers,
            body,
        });

        const data = await response.json();

        return { data, status: response.status };
    } catch (error) {
        console.error("POST error:", error);
        throw new Error("La requête a échoué");
    }
}

/***
 * Fait passer les requêtes HTTP en GET ou en POST,
 * si data est défini c'est la requête POST qui sera lancée,
 * autrement c'est la requête GET qui sera lancée
 * @param {String} [url=null]
 * @param {Object} form
 * @returns {data, status} data: http response if status equal 200 or 201
 */
export async function postJson(url, form) {
    try {
        var csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Content-Type": "application/json",
                accept: "application/json",
            },
            body: JSON.stringify(form),
        });
        const data = await response.json();
        return { data, status: response.status };
    } catch (error) {
        console.error("Error:", error);
        throw new Error("La requête a échoué");
    }
}

/**
 * Fait une requete en GET
 * @param {*} url
 * @returns {data, status} data: http response if status equal to 200 or 201
 */
export async function get(url) {
    const response = await fetch(url, {
        method: "GET",
        headers: {
            "Content-Type": "application/json",
            accept: "application/json",
        },
    });
    const data = await response.json();
    return { data, status: response.status };
}

export function objectToFormData(
    obj,
    formData = new FormData(),
    parentKey = null
) {
    Object.keys(obj).forEach((key) => {
        const value = obj[key];
        const formKey = parentKey ? `${parentKey}[${key}]` : key;

        if (value === null || value === undefined) {
            return;
        }

        if (value instanceof File) {
            formData.append(formKey, value);
        } else if (typeof value === "object" && !(value instanceof Date)) {
            objectToFormData(value, formData, formKey);
        } else {
            formData.append(formKey, value);
        }
    });

    return formData;
}
