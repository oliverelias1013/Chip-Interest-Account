<?php

declare(strict_types=1);

namespace Chip\Transaction;

interface TransactionRepositoryInterface
{
    public function save(Transaction $transaction): void;
    /** @return Transaction[] */
    public function findByAccountId(string $accountId): array;
}