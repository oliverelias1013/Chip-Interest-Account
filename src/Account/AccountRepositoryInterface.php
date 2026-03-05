<?php

declare(strict_types=1);

namespace Chip\Account;

interface AccountRepositoryInterface
{
    public function findByUserId(string $userId): ?Account;
    public function findById(string $accountId): ?Account;
    public function save(Account $account): void;
}