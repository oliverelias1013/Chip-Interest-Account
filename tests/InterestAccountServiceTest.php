<?php

declare(strict_types=1);

namespace Chip\Tests;

use Chip\Account\AccountRepository;
use Chip\Interest\InterestCalculator;
use Chip\InterestAccountService;
use Chip\Stats\StatsApiException;
use Chip\Stats\StatsClientInterface;
use Chip\Transaction\TransactionRepository;
use Chip\Transaction\TransactionType;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class InterestAccountServiceTest extends TestCase
{
    use ProphecyTrait;

    private const USER_ID = '88224979-406e-4e32-9458-55836e4e1f95';

    private InterestAccountService $service;
    private $statsClient;

    protected function setUp(): void
    {
        $this->statsClient = $this->prophesize(StatsClientInterface::class);

        $this->service = new InterestAccountService(
            accountRepository: new AccountRepository(),
            transactionRepository: new TransactionRepository(),
            statsClient: $this->statsClient->reveal(),
            interestCalculator: new InterestCalculator()
        );
    }

    // openAccount tests

    public function testOpenAccountWithLowIncome(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(300000);
        $account = $this->service->openAccount(self::USER_ID);
        $this->assertSame(self::USER_ID, $account->getUserId());
        $this->assertEqualsWithDelta(0.0093, $account->getInterestRate(), 0.00001);
    }

    public function testOpenAccountWithHighIncome(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(600000);
        $account = $this->service->openAccount(self::USER_ID);
        $this->assertEqualsWithDelta(0.0102, $account->getInterestRate(), 0.00001);
    }

    public function testOpenAccountWithUnknownIncome(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $account = $this->service->openAccount(self::USER_ID);
        $this->assertEqualsWithDelta(0.005, $account->getInterestRate(), 0.00001);
    }

    public function testOpenAccountWithIncomeExactlyAtThreshold(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(500000);
        $account = $this->service->openAccount(self::USER_ID);
        $this->assertEqualsWithDelta(0.0102, $account->getInterestRate(), 0.00001);
    }

    public function testCannotOpenDuplicateAccount(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $this->service->openAccount(self::USER_ID);
        $this->expectException(\LogicException::class);
        $this->service->openAccount(self::USER_ID);
    }

    public function testRejectsInvalidUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->openAccount('not-a-uuid');
    }

    public function testStatsApiFailureThrowsException(): void
    {
        $this->statsClient
            ->getMonthlyIncome(self::USER_ID)
            ->willThrow(new StatsApiException('Stats API unavailable'));
        $this->expectException(StatsApiException::class);
        $this->service->openAccount(self::USER_ID);
    }

    // deposit tests

    public function testDepositAddsToBalance(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $this->service->openAccount(self::USER_ID);
        $transaction = $this->service->deposit(self::USER_ID, 10000);
        $this->assertSame(TransactionType::DEPOSIT, $transaction->getType());
        $this->assertSame(10000, $transaction->getAmountPennies());
    }

    public function testDepositAppearsInStatement(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $this->service->openAccount(self::USER_ID);
        $this->service->deposit(self::USER_ID, 5000);
        $statement = $this->service->getStatement(self::USER_ID);
        $this->assertCount(1, $statement);
        $this->assertSame(TransactionType::DEPOSIT, $statement[0]->getType());
    }

    public function testCannotDepositToNonExistentAccount(): void
    {
        $this->expectException(\LogicException::class);
        $this->service->deposit(self::USER_ID, 5000);
    }

    public function testCannotDepositZeroOrNegative(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $this->service->openAccount(self::USER_ID);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->deposit(self::USER_ID, 0);
    }

    // calculateInterest tests

    public function testInterestPaidWhenAtLeastOnePenny(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $this->service->openAccount(self::USER_ID);
        $this->service->deposit(self::USER_ID, 1_000_000);
        $this->service->calculateInterest(self::USER_ID);
        $statement = $this->service->getStatement(self::USER_ID);
        $this->assertCount(2, $statement);
        $this->assertSame(TransactionType::INTEREST, $statement[1]->getType());
        $this->assertGreaterThanOrEqual(1, $statement[1]->getAmountPennies());
    }

    public function testInterestSkippedWhenLessThanOnePenny(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $this->service->openAccount(self::USER_ID);
        $this->service->deposit(self::USER_ID, 1);
        $this->service->calculateInterest(self::USER_ID);
        $statement = $this->service->getStatement(self::USER_ID);
        $this->assertCount(1, $statement);
    }

    public function testAccruedInterestIsCarriedForward(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $this->service->openAccount(self::USER_ID);
        $this->service->deposit(self::USER_ID, 1);
        for ($i = 0; $i < 5; $i++) {
            $this->service->calculateInterest(self::USER_ID);
        }
        $statement = $this->service->getStatement(self::USER_ID);
        $this->assertCount(1, $statement);
    }

    public function testAccruedInterestEventuallyPaysOut(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $this->service->openAccount(self::USER_ID);
        $this->service->deposit(self::USER_ID, 100000);
        $this->service->calculateInterest(self::USER_ID);
        $statement = $this->service->getStatement(self::USER_ID);
        $interestTransactions = array_filter(
            $statement,
            fn($t) => $t->getType() === TransactionType::INTEREST
        );
        $this->assertCount(1, $interestTransactions);
    }

    // getStatement tests

    public function testStatementContainsDepositsAndInterest(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(600000);
        $this->service->openAccount(self::USER_ID);
        $this->service->deposit(self::USER_ID, 500000);
        $this->service->calculateInterest(self::USER_ID);
        $statement = $this->service->getStatement(self::USER_ID);
        $types = array_map(fn($t) => $t->getType(), $statement);
        $this->assertContains(TransactionType::DEPOSIT, $types);
        $this->assertContains(TransactionType::INTEREST, $types);
    }

    public function testStatementIsEmptyBeforeAnyDeposit(): void
    {
        $this->statsClient->getMonthlyIncome(self::USER_ID)->willReturn(null);
        $this->service->openAccount(self::USER_ID);
        $this->assertEmpty($this->service->getStatement(self::USER_ID));
    }

    public function testCannotGetStatementForNonExistentAccount(): void
    {
        $this->expectException(\LogicException::class);
        $this->service->getStatement(self::USER_ID);
    }
}