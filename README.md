# Ledger Core

A casino game ledger built with **Laravel 12** and **PHP 8.4**. It's a small project I put together to tackle the kinds of problems you'd actually hit building financial systems — race conditions, decimal precision, audit trails, that sort of thing.

Not a production casino. Just a focused demo of how I'd approach the hard parts.

## What's in here

Four API endpoints behind Sanctum auth — spin, deposit, balance, and transaction history. The interesting stuff is under the hood.

### The good bits

- **Pessimistic locking** — every bet and deposit locks the user row with `lockForUpdate()` inside a DB transaction. No race conditions, no negative balances, no "oops we paid out twice" situations.
- **bcmath everywhere** — all money math uses `bcadd`, `bcsub`, `bcmul`, `bccomp` with 4 decimal places. No floating-point surprises.
- **DB-level safety net** — a `CHECK (balance >= 0)` constraint on the users table. Even if the app logic somehow fails, the database won't let a balance go negative.
- **Full audit trail** — every transaction gets logged with `balance_after`, plus a `TransactionObserver` that writes to a dedicated log channel with IP and session info.
- **Performance indexes** — composite indexes on `(user_id, created_at)`, `(user_id, type, created_at)`, and `(user_id, balance_after)` for the queries that actually get hit.

### Quality gates

GrumPHP runs on every commit:
- **PHPStan level 8** (with Larastan)
- **Pint** for code style
- **Pest** for tests

## k6 Load Test Results

All benchmarks run inside Docker (FrankenPHP + MySQL 8.4), 10 virtual users, default 30s duration.

### Spin (POST /api/spin) — write-heavy

The main endpoint. Each spin does: auth check → lock row → validate balance → create bet transaction → RNG → maybe create win transaction → unlock.

| Metric | Value |
|---|---|
| p95 response time | **161ms** |
| p99 response time | **577ms** |
| Success rate | **100%** |
| Failed requests | **0%** |
| Avg spin duration | **124ms** |
| Throughput | **~15 req/s** |
| All thresholds | **passed** |

### Balance (GET /api/balance) — read-heavy

Simple authenticated balance lookup.

| Metric | Value |
|---|---|
| p95 response time | **134ms** |
| p99 response time | **253ms** |
| Success rate | **99.92%** |
| Failed requests | **0.04%** |
| Avg balance duration | **110ms** |
| Throughput | **~31 req/s** |
| All thresholds | **passed** |

> Run them yourself: `docker compose --profile benchmark run --rm k6 run /scripts/scenarios/spin.js`

## Tech stack

- **Laravel 12** / **PHP 8.4** with strict types
- **FrankenPHP** as the app server
- **MySQL 8.4** with InnoDB row-level locking
- **Redis 7.4** for caching/sessions
- **Laravel Sanctum** for SPA authentication
- **Pest** for testing, **Larastan** (PHPStan L8) for static analysis
- **k6** for load testing, containerized alongside the app
- **Docker Compose** for the full stack

## Running it

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer setup
```

Tests:
```bash
docker compose exec app php artisan test
```

Load tests:
```bash
docker compose --profile benchmark run --rm k6 run /scripts/scenarios/spin.js
docker compose --profile benchmark run --rm k6 run /scripts/scenarios/balance-checks.js
docker compose --profile benchmark run --rm k6 run /scripts/scenarios/mixed-realistic.js
```

## Project layout

```
app/
├── Actions/
│   ├── Game/SimulateGameAction.php      # 40% win chance, 1.5x multiplier
│   └── Wallet/
│       ├── PlaceBetAction.php           # lock → validate → bet → RNG → win
│       └── DepositAction.php            # lock → add funds → log
├── Observers/TransactionObserver.php    # audit logging on every transaction
├── Exceptions/InsufficientBalanceException.php
└── Models/  (User, Game, Transaction)

tests/Feature/
├── ConcurrencyTest.php                  # race condition scenarios
├── PlaceBetTest.php                     # core betting flow
└── WalletIntegrityTest.php              # balance never negative, math checks

k6/scenarios/
├── spin.js                              # write-heavy load test
├── balance-checks.js                    # read-heavy load test
└── mixed-realistic.js                   # 50% spins, 30% balance, 20% deposits
```

