<?php

declare(strict_types=1);

namespace Chip\Transaction;

class TransactionRepository implements TransactionRepositoryInterface
{
    /** @var Transaction[] */
    private array $transactions = [];

    public function save(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }

    public function findByAccountId(string $accountId): array
    {
        return array_values(array_filter(
            $this->transactions,
            fn(Transaction $t) => $t->getAccountId() === $accountId
        ));
    }
}