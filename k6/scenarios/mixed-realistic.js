/**
 * Scenario 4 – Mixed Realistic Workload
 *
 * Simulates a realistic distribution of user actions:
 *   - 10 % logins (new sessions)
 *   - 30 % balance checks
 *   - 50 % spins (the core action)
 *   - 10 % deposit + transaction history
 *
 * Uses k6 scenarios to run all groups in parallel, giving you a combined
 * picture of system behavior under real-world traffic patterns.
 *
 * Usage:
 *   docker compose --profile benchmark run k6 run /scripts/scenarios/mixed-realistic.js
 *   K6_VUS=50 K6_DURATION=5m docker compose --profile benchmark run k6 run /scripts/scenarios/mixed-realistic.js
 */

import { sleep, check, group } from 'k6';
import http from 'k6/http';
import { Trend, Rate } from 'k6/metrics';
import { BASE_URL, VUS, DURATION } from '../helpers/config.js';
import { login, register, authHeaders, logout } from '../helpers/auth.js';

http.setResponseCallback(http.expectedStatuses({ min: 200, max: 299 }, 302, 422));

// ── Custom metrics ──────────────────────────────────────────────────────────
const actionDuration = new Trend('action_duration', true);
const actionSuccess  = new Rate('action_success_rate');

// ── k6 options ──────────────────────────────────────────────────────────────
export const options = {
    scenarios: {
        // Players who mostly spin
        spinners: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '15s', target: Math.ceil(VUS * 0.5) },
                { duration: DURATION, target: Math.ceil(VUS * 0.5) },
                { duration: '10s', target: 0 },
            ],
            exec: 'spinnerWorkload',
            gracefulStop: '10s',
        },
        // Players who check balance / transactions frequently
        browsers: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '15s', target: Math.ceil(VUS * 0.3) },
                { duration: DURATION, target: Math.ceil(VUS * 0.3) },
                { duration: '10s', target: 0 },
            ],
            exec: 'browserWorkload',
            gracefulStop: '10s',
        },
        // Players who deposit and then play a few rounds
        depositors: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '15s', target: Math.ceil(VUS * 0.2) },
                { duration: DURATION, target: Math.ceil(VUS * 0.2) },
                { duration: '10s', target: 0 },
            ],
            exec: 'depositorWorkload',
            gracefulStop: '10s',
        },
    },
    thresholds: {
        http_req_failed: ['rate<0.30'],
        'http_req_duration{name:spin}': ['p(95)<700', 'p(99)<1200'],
        'http_req_duration{name:balance}': ['p(95)<300', 'p(99)<600'],
        'http_req_duration{name:deposit}': ['p(95)<400', 'p(99)<900'],
        'http_req_duration{name:transactions}': ['p(95)<400', 'p(99)<900'],
        action_duration: ['p(95)<700'],
        action_success_rate: ['rate>0.95'],
    },
};

// ── Setup: create test users with funds ─────────────────────────────────────
export function setup() {
    const users = [];
    const startedAt = Date.now();

    for (let i = 0; i < VUS; i++) {
        const email    = `k6_mixed_${i}_${startedAt}@bench.test`;
        const password = 'BenchPass123!';
        const name     = `K6 Mixed User ${i}`;

        const xsrfToken = register(name, email, password);

        if (!xsrfToken) {
            continue;
        }

        const hdrs = authHeaders(xsrfToken);
        const depositRes = http.post(
            `${BASE_URL}/api/deposit`,
            JSON.stringify({ amount: 5000.00 }),
            { headers: hdrs, tags: { name: 'deposit' } },
        );
        if (depositRes.status !== 200) {
            logout();
            continue;
        }

        // Ensure each setup account can authenticate from a clean guest state.
        logout();
        const verifiedToken = login(email, password);

        if (!verifiedToken) {
            console.error(`[Setup] Skipping unverifiable user ${email}`);
            logout();
            continue;
        }

        logout();

        users.push({ email, password });
    }

    if (users.length === 0) {
        throw new Error('Setup failed – no test users created. Check console output above for details.');
    }

    console.log(`[Setup] Successfully created ${users.length} test users`);
    return { users };
}

// ═══════════════════════════════════════════════════════════════════════════
// Workload: Spinners (50 % of traffic)
//   Login → rapid spins → occasional balance check → logout
// ═══════════════════════════════════════════════════════════════════════════
export function spinnerWorkload(data) {
    const user = data.users[(__VU - 1) % data.users.length];
    const xsrfToken = login(user.email, user.password);

    if (!xsrfToken) {
        actionSuccess.add(false);
        sleep(1);
        return;
    }

    const hdrs = authHeaders(xsrfToken);

    group('spinner session', () => {
        // Several spins
        for (let i = 0; i < 8; i++) {
            const bet   = (Math.random() * 4.9 + 0.1).toFixed(2);
            const start = Date.now();
            const res   = http.post(
                `${BASE_URL}/api/spin`,
                JSON.stringify({ bet_amount: parseFloat(bet) }),
                { headers: hdrs, tags: { name: 'spin' } },
            );
            actionDuration.add(Date.now() - start);
            // 422 is expected (insufficient balance), treat as success
            actionSuccess.add(res.status === 200 || res.status === 422);

            // If insufficient balance, deposit and continue
            if (res.status === 422 || res.status === 400) {
                http.post(
                    `${BASE_URL}/api/deposit`,
                    JSON.stringify({ amount: 500.00 }),
                    { headers: hdrs, tags: { name: 'deposit' } },
                );
            }

            sleep(0.3 + Math.random() * 0.7);
        }

        // Quick balance check between spin bursts
        const balStart = Date.now();
        const balRes   = http.get(`${BASE_URL}/api/balance`, {
            headers: hdrs,
            tags: { name: 'balance' },
        });
        actionDuration.add(Date.now() - balStart);
        actionSuccess.add(balRes.status === 200);
    });

    logout();
    sleep(1);
}

// ═══════════════════════════════════════════════════════════════════════════
// Workload: Browsers (30 % of traffic)
//   Login → balance checks → transaction history → logout
// ═══════════════════════════════════════════════════════════════════════════
export function browserWorkload(data) {
    const user = data.users[(__VU - 1) % data.users.length];
    const xsrfToken = login(user.email, user.password);

    if (!xsrfToken) {
        actionSuccess.add(false);
        sleep(1);
        return;
    }

    const hdrs = authHeaders(xsrfToken);

    group('browser session', () => {
        // Multiple balance checks
        for (let i = 0; i < 5; i++) {
            const start = Date.now();
            const res   = http.get(`${BASE_URL}/api/balance`, {
                headers: hdrs,
                tags: { name: 'balance' },
            });
            actionDuration.add(Date.now() - start);
            actionSuccess.add(res.status === 200);
            sleep(0.5 + Math.random() * 1.0);
        }

        // Check transaction history
        for (let i = 0; i < 3; i++) {
            const start = Date.now();
            const res   = http.get(`${BASE_URL}/api/transactions`, {
                headers: hdrs,
                tags: { name: 'transactions' },
            });
            actionDuration.add(Date.now() - start);
            actionSuccess.add(res.status === 200);
            sleep(1.0 + Math.random() * 2.0);
        }
    });

    logout();
    sleep(1);
}

// ═══════════════════════════════════════════════════════════════════════════
// Workload: Depositors (20 % of traffic)
//   Login → deposit → a couple of spins → check transactions → logout
// ═══════════════════════════════════════════════════════════════════════════
export function depositorWorkload(data) {
    const user = data.users[(__VU - 1) % data.users.length];
    const xsrfToken = login(user.email, user.password);

    if (!xsrfToken) {
        actionSuccess.add(false);
        sleep(1);
        return;
    }

    const hdrs = authHeaders(xsrfToken);

    group('depositor session', () => {
        // Deposit
        const depAmount = (Math.random() * 900 + 100).toFixed(2);
        const depStart  = Date.now();
        const depRes    = http.post(
            `${BASE_URL}/api/deposit`,
            JSON.stringify({ amount: parseFloat(depAmount) }),
            { headers: hdrs, tags: { name: 'deposit' } },
        );
        actionDuration.add(Date.now() - depStart);
        actionSuccess.add(depRes.status === 200);
        sleep(1);

        // Check new balance
        const balStart = Date.now();
        const balRes   = http.get(`${BASE_URL}/api/balance`, {
            headers: hdrs,
            tags: { name: 'balance' },
        });
        actionDuration.add(Date.now() - balStart);
        actionSuccess.add(balRes.status === 200);
        sleep(0.5);

        // Play a few rounds
        for (let i = 0; i < 3; i++) {
            const bet   = (Math.random() * 9.9 + 0.1).toFixed(2);
            const start = Date.now();
            const res   = http.post(
                `${BASE_URL}/api/spin`,
                JSON.stringify({ bet_amount: parseFloat(bet) }),
                { headers: hdrs, tags: { name: 'spin' } },
            );
            actionDuration.add(Date.now() - start);
            // 422 is expected (insufficient balance), treat as success
            actionSuccess.add(res.status === 200 || res.status === 422);
            sleep(0.5 + Math.random() * 0.5);
        }

        // Review transactions
        const txStart = Date.now();
        const txRes   = http.get(`${BASE_URL}/api/transactions`, {
            headers: hdrs,
            tags: { name: 'transactions' },
        });
        actionDuration.add(Date.now() - txStart);
        actionSuccess.add(txRes.status === 200);
    });

    logout();
    sleep(1);
}
