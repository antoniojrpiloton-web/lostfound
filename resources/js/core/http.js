const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export class HttpError extends Error {
    constructor(response, payload) {
        super(payload?.message ?? `Request failed with status ${response.status}`);
        this.name = 'HttpError';
        this.status = response.status;
        this.payload = payload;
    }
}

export async function request(url, { method = 'GET', body = null, headers = {}, signal } = {}) {
    const isFormData = body instanceof FormData;
    const options = {
        method,
        signal,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(method !== 'GET' ? { 'X-CSRF-TOKEN': csrfToken() } : {}),
            ...(body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
            ...headers,
        },
    };

    if (body) {
        options.body = isFormData ? body : JSON.stringify(body);
    }

    const response = await fetch(url, options);
    const contentType = response.headers.get('content-type') ?? '';
    const payload = contentType.includes('application/json') ? await response.json() : await response.text();

    if (!response.ok) {
        throw new HttpError(response, typeof payload === 'object' ? payload : { message: payload });
    }

    return payload;
}

export const http = {
    get: (url, options = {}) => request(url, { ...options, method: 'GET' }),
    post: (url, body, options = {}) => request(url, { ...options, method: 'POST', body }),
    put: (url, body, options = {}) => request(url, { ...options, method: 'PUT', body }),
    patch: (url, body, options = {}) => request(url, { ...options, method: 'PATCH', body }),
    delete: (url, options = {}) => request(url, { ...options, method: 'DELETE' }),
};
