<?php

declare(strict_types=1);

namespace Chip;

use Chip\Account\Account;
use Chip\Account\AccountRepositoryInterface;
use Chip\Interest\InterestCalculator;
use Chip\Stats\StatsClientInterface;
use Chip\Transaction\Transaction;
use Chip\Transaction\TransactionRepositoryInterface;
use Chip\Transaction\TransactionType;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

class InterestAccountService
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly StatsClientInterface $statsClient,
        private readonly InterestCalculator $interestCalculator
    ) {}

    public function openAccount(string $userId): Account
    {
        if (!$this->isValidUuidV4($userId)) {
            throw new InvalidArgumentException("User ID must be a valid UUIDv4.");
        }

        if ($this->accountRepository->findByUserId($userId) !== null) {
            throw new LogicException("User already has an active interest account.");
        }

        $income = $this->statsClient->getMonthlyIncome($userId);
        $rate = $this->interestCalculator->determineRate($income);

        $account = new Account(
            id: $this->generateUuid(),
            userId: $userId,
            interestRate: $rate,
            openedAt: new DateTimeImmutable()
        );

        $this->accountRepository->save($account);

        return $account;
    }

    public function deposit(string $userId, int $amountPennies): Transaction
    {
        $account = $this->requireAccount($userId);

        $account->deposit($amountPennies);
        $this->accountRepository->save($account);

        $transaction = new Transaction(
            id: $this->generateUuid(),
            accountId: $account->getId(),
            amountPennies: $amountPennies,
            type: TransactionType::DEPOSIT,
            createdAt: new DateTimeImmutable()
        );

        $this->transactionRepository->save($transaction);

        return $transaction;
    }

    public function calculateInterest(string $userId): void
    {
        $account = $this->requireAccount($userId);

        $rawInterest = $this->interestCalculator->calculateForThreeDays(
            $account->getBalancePennies(),
            $account->getInterestRate()
        );

        $totalRaw = $rawInterest + $account->getAccruedRawPennies();
        $payoutPennies = (int) floor($totalRaw);

        if ($payoutPennies >= 1) {
            $account->creditInterest($payoutPennies);
            $this->accountRepository->save($account);

            $transaction = new Transaction(
                id: $this->generateUuid(),
                accountId: $account->getId(),
                amountPennies: $payoutPennies,
                type: TransactionType::INTEREST,
                createdAt: new DateTimeImmutable()
            );

            $this->transactionRepository->save($transaction);
        } else {
            $account->carryForwardInterest($totalRaw);
            $this->accountRepository->save($account);
        }
    }

    public function getStatement(string $userId): array
    {
        $account = $this->requireAccount($userId);
        return $this->transactionRepository->findByAccountId($account->getId());
    }

    private function requireAccount(string $userId): Account
    {
        $account = $this->accountRepository->findByUserId($userId);
        if ($account === null) {
            throw new LogicException("No active interest account found for user: {$userId}");
        }
        return $account;
    }

    private function isValidUuidV4(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}