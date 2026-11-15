import axios from 'axios';

// Same-origin JSON client. Laravel's XSRF-TOKEN cookie is attached automatically by axios.
const api = axios.create({
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    withCredentials: true,
});

/** Flatten a Laravel 422 response into { field: 'first message' }. */
export function validationErrors(error) {
    const errs = error?.response?.data?.errors;
    if (!errs) return {};
    return Object.fromEntries(Object.entries(errs).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
}

export function errorMessage(error, fallback = 'Something went wrong. Please try again.') {
    return error?.response?.data?.message || fallback;
}

export default api;
