/**
 * Scenario 2 – Balance Checks
 *
 * Hammers the GET /api/balance endpoint — a read-heavy, highly cacheable path.
 * Measures raw read throughput and response times under sustained concurrent load.
 *
 * Useful for: verifying cache effectiveness, database read scaling, CDN sizing.
 *
 * Usage:
 *   docker compose --profile benchmark run k6 run /scripts/scenarios/balance-checks.js
 *   K6_VUS=100 K6_DURATION=3m docker compose --profile benchmark run k6 run /scripts/scenarios/balance-checks.js
 */

import { sleep, check } from 'k6';
import http from 'k6/http';
import { Counter, Trend, Rate } from 'k6/metrics';
import { BASE_URL, VUS, DURATION, defaultThresholds } from '../helpers/config.js';
import { login, register, authHeaders } from '../helpers/auth.js';

http.setResponseCallback(http.expectedStatuses({ min: 200, max: 299 }, 302));

// ── Custom metrics ──────────────────────────────────────────────────────────
const balanceDuration = new Trend('balance_duration', true);
const balanceSuccess  = new Counter('balance_success');
const balanceRate     = new Rate('balance_success_rate');

// ── k6 options ──────────────────────────────────────────────────────────────
export const options = {
    scenarios: {
        balance_load: {
            executor: 'ramping-vus',
            startVUs: 1,
            stages: [
                { duration: '10s', target: Math.ceil(VUS * 0.3) },
                { duration: '10s', target: VUS },
                { duration: DURATION, target: VUS },
                { duration: '10s', target: 0 },
            ],
            gracefulStop: '5s',
        },
    },
    thresholds: Object.assign({}, defaultThresholds, {
        balance_duration:     ['p(95)<200', 'p(99)<400'],
        balance_success_rate: ['rate>0.98'],
    }),
};

// ── Setup: create & fund one user per VU ────────────────────────────────────
export function setup() {
    const users = [];
    const startedAt = Date.now();

    for (let i = 0; i < VUS; i++) {
        const email    = `k6_balance_${i}_${startedAt}@bench.test`;
        const password = 'BenchPass123!';
        const name     = `K6 Balance User ${i}`;

        const xsrfToken = register(name, email, password);

        if (!xsrfToken) {
            continue;
        }

        const hdrs = authHeaders(xsrfToken);
        http.post(
            `${BASE_URL}/api/deposit`,
            JSON.stringify({ amount: 500.00 }),
            { headers: hdrs, tags: { name: 'deposit' } },
        );
        http.post(`${BASE_URL}/logout`, null, { headers: hdrs, tags: { name: 'logout' } });

        users.push({ email, password });
    }

    if (users.length === 0) {
        throw new Error('Setup failed – no test users created. Check console output above for details.');
    }

    console.log(`[Setup] Successfully created ${users.length} test users`);

    return { users };
}

// ── Main test function ──────────────────────────────────────────────────────
export default function (data) {
    const user = data.users[(__VU - 1) % data.users.length];

    const xsrfToken = login(user.email, user.password);

    if (!xsrfToken) {
        balanceRate.add(false);
        sleep(1);
        return;
    }

    const hdrs = authHeaders(xsrfToken);

    for (let i = 0; i < 10; i++) {
        const start = Date.now();
        const res   = http.get(`${BASE_URL}/api/balance`, {
            headers: hdrs,
            tags: { name: 'balance' },
        });
        balanceDuration.add(Date.now() - start);

        const ok = check(res, {
            'balance 200':    (r) => r.status === 200,
            'has balance':    (r) => {
                try { return JSON.parse(r.body).success === true; }
                catch { return false; }
            },
        });
        balanceSuccess.add(ok ? 1 : 0);
        balanceRate.add(ok);

        sleep(0.1);
    }

    // Logout
    http.post(`${BASE_URL}/logout`, null, { headers: hdrs, tags: { name: 'logout' } });
    sleep(0.3);
}
