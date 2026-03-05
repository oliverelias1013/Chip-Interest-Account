<?php

declare(strict_types=1);

namespace Chip\Account;

class AccountRepository implements AccountRepositoryInterface
{
    /** @var array<string, Account> */
    private array $accounts = [];

    public function findByUserId(string $userId): ?Account
    {
        foreach ($this->accounts as $account) {
            if ($account->getUserId() === $userId) {
                return $account;
            }
        }
        return null;
    }

    public function findById(string $accountId): ?Account
    {
        return $this->accounts[$accountId] ?? null;
    }

    public function save(Account $account): void
    {
        $this->accounts[$account->getId()] = $account;
    }
}