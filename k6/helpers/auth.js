/**
 * Sanctum SPA cookie-based authentication helper for k6.
 *
 * Flow:
 *   1. GET  /sanctum/csrf-cookie  → receive XSRF-TOKEN cookie
 *   2. POST /login                → establish session (laravel_session cookie)
 *   3. All subsequent requests carry both cookies + X-XSRF-TOKEN header
 *
 * k6's built-in cookie jar handles cookie persistence per VU automatically.
 * We only need to extract the XSRF-TOKEN value and forward it as a header.
 */

import http from 'k6/http';
import { check } from 'k6';
import { BASE_URL, defaultHeaders } from './config.js';

function fetchXsrfToken() {
    const csrfRes = http.get(`${BASE_URL}/sanctum/csrf-cookie`, {
        headers: defaultHeaders,
        tags: { name: 'csrf-cookie' },
    });

    if (csrfRes.status !== 200 && csrfRes.status !== 204) {
        return null;
    }

    const jar = http.cookieJar();
    const xsrfCookie = jar.cookiesForURL(BASE_URL)['XSRF-TOKEN'];

    if (!xsrfCookie || xsrfCookie.length === 0) {
        return null;
    }

    return decodeURIComponent(xsrfCookie[0]);
}

/**
 * Authenticate a virtual user and return the XSRF token for subsequent calls.
 *
 * @param {string} email
 * @param {string} password
 * @returns {string} xsrfToken – value to send as X-XSRF-TOKEN header
 */
export function login(email, password) {
    const xsrfToken = fetchXsrfToken();

    if (!xsrfToken) {
        console.error('[Auth] Failed to fetch XSRF-TOKEN before login');
        return null;  // Return null instead of failing
    }

    // Step 2 – Login
    const loginRes = http.post(
        `${BASE_URL}/login`,
        JSON.stringify({ email, password }),
        {
            headers: Object.assign({}, defaultHeaders, {
                'X-XSRF-TOKEN': xsrfToken,
            }),
            tags: { name: 'login' },
            redirects: 0,  // Don't follow redirects, we need to see the Set-Cookie header
        },
    );

    const loginOk = check(loginRes, {
        'login successful': (r) => r.status === 200 || r.status === 302 || r.status === 204,
    });
    
    if (!loginOk) {
        console.error(`[Auth] Login failed for ${email}: status=${loginRes.status}, body=${loginRes.body.substring(0, 200)}`);
        return null;  // Return null instead of failing
    }
    
    // Verify session cookie was set (check for both standard and custom session names)
    const jar = http.cookieJar();
    const allCookies = jar.cookiesForURL(BASE_URL);
    const sessionCookie = allCookies['laravel_session'] || allCookies['ledger-core-session'];
    
    if (!sessionCookie || sessionCookie.length === 0) {
        console.error(`[Auth] No session cookie after login. Response status: ${loginRes.status}`);
        console.error(`[Auth] Available cookies: ${JSON.stringify(Object.keys(allCookies))}`);
        return null;  // Return null instead of failing
    }

    const postLoginXsrfToken = fetchXsrfToken();
    return postLoginXsrfToken || xsrfToken;
}

/**
 * Build request headers that include the XSRF token and manually include cookies.
 *
 * @param {string} xsrfToken
 * @returns {object}
 */
export function authHeaders(xsrfToken) {
    const jar = http.cookieJar();
    const cookies = jar.cookiesForURL(BASE_URL);
    
    // Manually construct Cookie header to ensure cookies are sent
    const cookiePairs = [];
    for (const [name, values] of Object.entries(cookies)) {
        if (values && values.length > 0) {
            cookiePairs.push(`${name}=${values[0]}`);
        }
    }
    
    const headers = Object.assign({}, defaultHeaders, {
        'X-XSRF-TOKEN': xsrfToken,
    });
    
    if (cookiePairs.length > 0) {
        headers['Cookie'] = cookiePairs.join('; ');
    }
    
    return headers;
}

/**
 * Logout current session and clear cookies from the jar for a clean auth state.
 *
 * @returns {void}
 */
export function logout() {
    const xsrfToken = fetchXsrfToken();

    if (xsrfToken) {
        const headers = authHeaders(xsrfToken);
        http.post(`${BASE_URL}/logout`, null, {
            headers,
            tags: { name: 'logout' },
            redirects: 0,
        });
    }

    const jar = http.cookieJar();
    jar.clear(BASE_URL);
}

/**
 * Register a brand-new user (useful for setup / per-VU init).
 *
 * @param {string} name
 * @param {string} email
 * @param {string} password
 * @returns {string} xsrfToken
 */
export function register(name, email, password) {
    const xsrfToken = fetchXsrfToken();

    if (!xsrfToken) {
        console.error('[Auth] Failed to fetch XSRF-TOKEN before register');
        return null;
    }

    const regRes = http.post(
        `${BASE_URL}/register`,
        JSON.stringify({
            name,
            email,
            password,
            password_confirmation: password,
        }),
        {
            headers: Object.assign({}, defaultHeaders, {
                'X-XSRF-TOKEN': xsrfToken,
            }),
            tags: { name: 'register' },
            redirects: 0,
        },
    );

    const registerOk = check(regRes, {
        'registration successful': (r) => r.status === 200 || r.status === 302 || r.status === 204,
    });

    if (!registerOk) {
        console.error(`[Auth] Registration failed for ${email}: status=${regRes.status}, body=${regRes.body.substring(0, 200)}`);
        return null;
    }

    const postRegisterXsrfToken = fetchXsrfToken();
    return postRegisterXsrfToken || xsrfToken;
}
