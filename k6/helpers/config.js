/**
 * Shared configuration for all k6 benchmark scripts.
 *
 * Environment variable overrides (set via docker-compose or CLI):
 *   BASE_URL   – target host  (default: http://app:80)
 *   K6_VUS     – virtual users (default: 10)
 *   K6_DURATION – test length  (default: 30s)
 */

export const BASE_URL = __ENV.BASE_URL || 'http://app:80';
export const VUS      = parseInt(__ENV.K6_VUS     || '10', 10);
export const DURATION = __ENV.K6_DURATION || '30s';

/** Standard HTTP params sent with every request. */
export const defaultHeaders = {
    'Accept':       'application/json',
    'Content-Type': 'application/json',
    'Referer':      BASE_URL,   // Sanctum SPA requires a matching Referer
    'Origin':       BASE_URL,
};

/**
 * Default thresholds – fail the run if any of these are breached.
 * Individual scripts can override / extend these.
 * 
 * Note: 422 (insufficient balance) is expected behavior in gaming scenarios,
 * so we allow up to 30% client errors.
 */
export const defaultThresholds = {
    http_req_failed:   ['rate<0.30'],          // <30% errors (422 is expected)
    http_req_duration: ['p(95)<500', 'p(99)<1000'], // p95 < 500 ms, p99 < 1 s
};
