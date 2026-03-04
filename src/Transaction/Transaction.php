<?php

declare(strict_types=1);

namespace Chip\Transaction;

use DateTimeImmutable;

class Transaction
{
    public function __construct(
        private readonly string $id,
        private readonly string $accountId,
        private readonly int $amountPennies,
        private readonly TransactionType $type,
        private readonly DateTimeImmutable $createdAt
    ) {}

    public function getId(): string { return $this->id; }
    public function getAccountId(): string { return $this->accountId; }
    public function getAmountPennies(): int { return $this->amountPennies; }
    public function getType(): TransactionType { return $this->type; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}