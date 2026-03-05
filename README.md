cat > README.md << 'EOF'
# Chip Interest Account Library

A PHP library providing interest account functionality.

## Requirements

- PHP 8.2+
- Composer

## Installation
```bash
composer install
```

## Running Tests
```bash
vendor/bin/phpunit
```

## Usage
```php
use Chip\InterestAccountService;
use Chip\Account\AccountRepository;
use Chip\Transaction\TransactionRepository;
use Chip\Interest\InterestCalculator;
use Chip\Stats\StatsClient;

$service = new InterestAccountService(
    new AccountRepository(),
    new TransactionRepository(),
    new StatsClient(),
    new InterestCalculator()
);

// Open an account (fetches income from Stats API to determine rate)
$account = $service->openAccount('88224979-406e-4e32-9458-55836e4e1f95');

// Deposit £100 (amounts in pennies)
$service->deposit('88224979-406e-4e32-9458-55836e4e1f95', 10000);

// Run every 3 days via a scheduler
$service->calculateInterest('88224979-406e-4e32-9458-55836e4e1f95');

// Get full statement
$transactions = $service->getStatement('88224979-406e-4e32-9458-55836e4e1f95');
```