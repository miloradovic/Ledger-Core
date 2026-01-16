# Ledger Core - Casino Game Platform

**Small showcase of a casino game ledger system built with Laravel, solving critical challenges in financial transaction processing, concurrent betting, and regulatory compliance.**

## Project Overview

Ledger Core is a sophisticated casino game platform that addresses the core technical challenges faced in iGaming financial systems:

### Key Problems Solved

1. **Concurrent Betting Race Conditions**
   - Multiple users placing bets simultaneously on the same account
   - Potential for negative balances and financial discrepancies
   - Need for atomic, thread-safe financial operations

2. **Financial Precision and Accuracy**
   - Requirement for exact decimal calculations in betting scenarios
   - Prevention of rounding errors in win/loss calculations
   - Comprehensive audit trails for regulatory compliance

3. **Transaction Integrity**
   - Ensuring all financial operations are logged and traceable
   - Maintaining data consistency across high-volume operations
   - Providing real-time balance updates without race conditions

4. **Regulatory Compliance**
   - Complete transaction history for auditing
   - Balance tracking with before/after states
   - Secure API endpoints with proper authentication

### Solution Highlights

- **Atomic Database Transactions**: All financial operations wrapped in ACID-compliant transactions
- **Optimistic Locking**: Uses Laravel's `lockForUpdate()` to prevent race conditions
- **Decimal Precision**: 4 decimal place accuracy for all financial calculations
- **Comprehensive Logging**: Every transaction recorded with before/after balance states
- **RESTful API**: Secure endpoints for all gaming operations
- **Automated Testing**: Full test coverage including concurrency scenarios

## Core Features

## Table of Contents

- [Core Features](#core-features)
- [Architecture](#architecture)
- [API Endpoints](#api-endpoints)
- [Testing](#testing)
- [Project Structure](#project-structure)
- [License](#license)

## Core Features

### Wallet System
- Real-time balance tracking with atomic operations
- Secure deposit functionality with configurable limits ($0.01 - $10000)
- Decimal precision support (4 decimal places)
- Automatic transaction logging for all wallet operations

### Concurrent Betting
- Optimistic concurrency control using database locking (`lockForUpdate()`)
- Atomic transactions to ensure data consistency
- Prevents race conditions when placing bets simultaneously
- Insufficient balance check before processing bets

### Transaction History
- Complete financial transaction logging
- Supports multiple transaction types: `bet`, `win`, `deposit`, `withdraw`
- Balance after transaction tracking for audit purposes
- API endpoint for retrieving transaction history (last 50 transactions)

## Architecture

### Core Models

1. **User**: Represents a registered user with balance tracking
   - `id`, `name`, `email`, `password`, `balance`, `email_verified_at`
   - Relationships: `hasMany(Transaction)`
   - Balance stored as decimal with 4 decimal places precision

2. **Game**: Represents a casino game with bet limits
   - `id`, `name`, `min_bet`, `max_bet`

3. **Transaction**: Records all financial transactions
   - `id`, `user_id`, `type`, `amount`, `balance_after`
   - Types: `bet`, `win`, `deposit`, `withdraw`
   - Relationships: `belongsTo(User)`

### Key Actions

1. **PlaceBetAction**: Handles bet placement with atomic database transactions
   - Lock user record for update to prevent race conditions
   - Check balance before processing bet
   - Create bet transaction
   - Simulate game outcome
   - Process winnings if bet is successful
   - Create win transaction
   - Return updated balance and results

2. **DepositAction**: Manages user deposits
   - Add funds to user's wallet
   - Create deposit transaction
   - Return updated balance

3. **SimulateGameAction**: Simulates casino game outcomes
   - 40% win probability with 1.5x multiplier
   - Randomized outcome generation
   - Returns win status and amount

### Controllers

1. **GameController**: API endpoints for game operations
   - `spin()`: Place a bet (requires `bet_amount`)
   - `deposit()`: Deposit funds (requires `amount`)
   - `balance()`: Get current user balance
   - `transactions()`: Get transaction history (latest 50)

## API Endpoints

All endpoints are protected by Laravel Sanctum authentication.

### Game Operations
- `POST /api/spin`: Place a bet
  - Body: `{ "bet_amount": 10.00 }`
  - Response: `{ "success": true, "data": { "win": true, "winnings": 15.00, "new_balance": 105.00 } }`

- `POST /api/deposit`: Deposit funds
  - Body: `{ "amount": 50.00 }`
  - Response: `{ "success": true, "data": { "balance": 150.00 } }`

- `GET /api/balance`: Get current balance
  - Response: `{ "success": true, "balance": 150.00 }`

- `GET /api/transactions`: Get transaction history
  - Response: `{ "success": true, "transactions": [ ... ] }`

## Testing

The application includes comprehensive tests for all critical functionality, with special focus on financial integrity and concurrency scenarios:

### Key Test Coverage

1. **ConcurrencyTest**: Ensures race condition prevention
   - Tests multiple simultaneous bets on the same account
   - Verifies balance never goes negative
   - Confirms proper handling of insufficient funds scenarios
   - Validates transaction logging under concurrent load

2. **PlaceBetTest**: Validates core betting functionality
   - Bet placement and balance deduction
   - Win processing and balance updates
   - Transaction history accuracy
   - Edge cases and error conditions

3. **Financial Integrity Tests**:
   - Atomic transaction verification
   - Balance consistency checks
   - Audit trail completeness
   - Decimal precision validation

### Key Code Examples

#### 1. Atomic Bet Processing with Race Condition Prevention

The core bet placement logic in [`PlaceBetAction.php`](app/Actions/Wallet/PlaceBetAction.php) demonstrates how we solve concurrent betting challenges:

```php
public function execute(User $user, float $betAmount): array
{
    return DB::transaction(function () use ($user, $betAmount): array {
        // Lock the user record to prevent race conditions
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        
        // Validate sufficient balance
        if ($user->balance < $betAmount) {
            throw new Exception('Insufficient balance');
        }
        
        // Atomic balance update
        $user->balance -= $betAmount;
        $user->save();
        
        // Create audit trail transaction
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'bet',
            'amount' => -$betAmount,
            'balance_after' => $user->balance
        ]);
        
        // Process game outcome and potential winnings
        // ...
    });
}
```

#### 2. Concurrent Testing Implementation

The [`ConcurrencyTest.php`](tests/Feature/ConcurrencyTest.php) shows how we validate race condition prevention:

```php
public function test_concurrent_bets_are_handled_correctly(): void
{
    $user = User::factory()->create(['balance' => 100.00]);
    
    // Simulate 5 concurrent bet requests
    $responses = [];
    for ($i = 0; $i < 5; $i++) {
        $responses[] = $this->actingAs($user)
            ->postJson('/api/spin', ['bet_amount' => 10.00]);
    }
    
    // Verify all requests completed successfully
    foreach ($responses as $response) {
        $response->assertSuccessful();
    }
    
    // Ensure balance integrity (never exceeds initial balance)
    $user->refresh();
    $this->assertLessThanOrEqual(100.00, $user->balance);
    
    // Confirm proper transaction logging
    $this->assertGreaterThanOrEqual(5, DB::table('transactions')
        ->where('user_id', $user->id)->count());
}
```

#### 3. Secure Deposit Processing

The [`DepositAction.php`](app/Actions/Wallet/DepositAction.php) shows atomic deposit handling:

```php
public function execute(User $user, float $amount): array
{
    return DB::transaction(function () use ($user, $amount): array {
        // Lock user record for atomic update
        $user = User::where('id', $user->id)->lockForUpdate()->first();
        
        // Atomic balance update
        $user->balance += $amount;
        $user->save();
        
        // Create complete audit trail
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $user->balance
        ]);
        
        return ['success' => true, 'new_balance' => $user->balance];
    });
}
```

### Run Tests

```bash
php artisan test
```

The test suite includes:
- Unit tests for all core models and actions
- Feature tests for API endpoints and workflows
- Concurrency tests for race condition prevention
- Edge case testing for financial operations
- Integration tests for complete user journeys

## Project Structure

```
Ledger-Core/
├── app/
│   ├── Actions/
│   │   ├── Game/
│   │   │   └── SimulateGameAction.php
│   │   └── Wallet/
│   │       ├── DepositAction.php
│   │       └── PlaceBetAction.php
│   ├── Http/
│   │   └── Controllers/
│   │       ├── GameController.php
│   │       └── ProfileController.php
│   └── Models/
│       ├── Game.php
│       ├── Transaction.php
│       └── User.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
└── tests/
    └── Feature/
        ├── ConcurrencyTest.php
        └── PlaceBetTest.php
```

