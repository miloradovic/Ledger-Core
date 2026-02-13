/**
 * Scenario 3 – Spin (Core Game Loop)
 *
 * Exercises the write-heavy POST /api/spin path that involves:
 *   - Balance validation
 *   - Transaction creation (bet + potential win)
 *   - Game logic / RNG
 *   - Balance updates
 *
 * This is the most resource-intensive endpoint and the primary bottleneck
 * for peak-load planning.
 *
 * Usage:
 *   docker compose --profile benchmark run k6 run /scripts/scenarios/spin.js
 *   K6_VUS=30 K6_DURATION=2m docker compose --profile benchmark run k6 run /scripts/scenarios/spin.js
 */

import { sleep, check } from 'k6';
import http from 'k6/http';
import { Counter, Trend, Rate } from 'k6/metrics';
import { BASE_URL, VUS, DURATION, defaultThresholds } from '../helpers/config.js';
import { login, register, authHeaders, logout } from '../helpers/auth.js';

http.setResponseCallback(http.expectedStatuses({ min: 200, max: 299 }, 302, 422));

// ── Custom metrics ──────────────────────────────────────────────────────────
const spinDuration  = new Trend('spin_duration', true);
const spinSuccess   = new Counter('spin_success');
const spinFailure   = new Counter('spin_failure');
const spinWins      = new Counter('spin_wins');
const spinRate      = new Rate('spin_success_rate');
const depositDuration = new Trend('deposit_duration', true);

// ── k6 options ──────────────────────────────────────────────────────────────
export const options = {
    scenarios: {
        spin_load: {
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
        http_req_duration: ['p(95)<600', 'p(99)<1500'],
        spin_duration:     ['p(95)<600', 'p(99)<1200'],
        spin_success_rate: ['rate>0.95'],
        http_req_failed:   ['rate<0.10'],
    }),
};

// ── Setup: create users (deposits happen in main test) ──────────────────────
export function setup() {
    const users = [];
    const startedAt = Date.now();

    for (let i = 0; i < VUS; i++) {
        const email    = `k6_spin_${i}_${startedAt}@bench.test`;
        const password = 'BenchPass123!';
        const name     = `K6 Spin User ${i}`;

        const xsrfToken = register(name, email, password);

        if (xsrfToken) {
            // Ensure the user can authenticate from a clean guest state.
            logout();
            const verifiedToken = login(email, password);

            if (verifiedToken) {
                users.push({ email, password });
                logout();
            } else {
                console.error(`[Setup] Skipping unverifiable user ${email}`);
            }
        }
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
        spinFailure.add(1);
        spinRate.add(false);
        sleep(1);
        return;
    }

    const hdrs = authHeaders(xsrfToken);

    // Fund account once per session.
    const initialDepositStart = Date.now();
    const initialDeposit = http.post(
        `${BASE_URL}/api/deposit`,
        JSON.stringify({ amount: 5000.00 }),
        { headers: hdrs, tags: { name: 'deposit' } },
    );
    depositDuration.add(Date.now() - initialDepositStart);

    if (initialDeposit.status !== 200) {
        spinFailure.add(1);
        spinRate.add(false);
        sleep(0.5);
        return;
    }

    // Spin loop – simulate a player session (multiple spins)
    const spinsPerSession = 5;
    let needsDeposit      = false;

    for (let i = 0; i < spinsPerSession; i++) {
        // Re-deposit if we ran low on a previous iteration
        if (needsDeposit) {
            const depStart = Date.now();
            const depRes   = http.post(
                `${BASE_URL}/api/deposit`,
                JSON.stringify({ amount: 1000.00 }),
                { headers: hdrs, tags: { name: 'deposit' } },
            );
            depositDuration.add(Date.now() - depStart);
            check(depRes, { 'deposit ok': (r) => r.status === 200 });
            needsDeposit = false;
        }

        // Place bet (random amount between 0.10 and 5.00)
        const betAmount = (Math.random() * 4.9 + 0.1).toFixed(2);
        const start     = Date.now();

        const spinRes = http.post(
            `${BASE_URL}/api/spin`,
            JSON.stringify({ bet_amount: parseFloat(betAmount) }),
            { headers: hdrs, tags: { name: 'spin' } },
        );
        spinDuration.add(Date.now() - start);

        const isSuccess = spinRes.status === 200 || spinRes.status === 422;
        check(spinRes, {
            'spin accepted': (r) => r.status === 200 || r.status === 422,
        });

        if (isSuccess) {
            spinSuccess.add(1);
            spinRate.add(true);

            if (spinRes.status === 200) {
                try {
                    const body = JSON.parse(spinRes.body);
                    if (body.data && body.data.win) {
                        spinWins.add(1);
                    }
                } catch { /* ignore */ }
            } else {
                needsDeposit = true;
            }
        } else {
            spinFailure.add(1);
            spinRate.add(false);
        }

        // Brief pause between spins (simulates human reaction time)
        sleep(0.3 + Math.random() * 0.5);
    }

    http.post(`${BASE_URL}/logout`, null, { headers: hdrs, tags: { name: 'logout' } });
    sleep(0.5);
}
