<?php

declare(strict_types=1);

namespace Chip\Account;

use DateTimeImmutable;
use InvalidArgumentException;

class Account
{
    private int $balancePennies = 0;
    private float $accruedRawPennies = 0.0;
    private ?DateTimeImmutable $lastInterestPaidAt = null;

    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly float $interestRate,
        private readonly DateTimeImmutable $openedAt
    ) {}

    public function getId(): string { return $this->id; }
    public function getUserId(): string { return $this->userId; }
    public function getInterestRate(): float { return $this->interestRate; }
    public function getBalancePennies(): int { return $this->balancePennies; }
    public function getAccruedRawPennies(): float { return $this->accruedRawPennies; }
    public function getOpenedAt(): DateTimeImmutable { return $this->openedAt; }
    public function getLastInterestPaidAt(): ?DateTimeImmutable { return $this->lastInterestPaidAt; }

    public function deposit(int $pennies): void
    {
        if ($pennies <= 0) {
            throw new InvalidArgumentException('Deposit amount must be positive.');
        }
        $this->balancePennies += $pennies;
    }

    public function creditInterest(int $pennies): void
    {
        $this->balancePennies += $pennies;
        $this->accruedRawPennies = 0.0;
        $this->lastInterestPaidAt = new DateTimeImmutable();
    }

    public function carryForwardInterest(float $rawPennies): void
    {
        $this->accruedRawPennies += $rawPennies;
        $this->lastInterestPaidAt = new DateTimeImmutable();
    }
}