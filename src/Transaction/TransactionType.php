<?php

declare(strict_types=1);

namespace Chip\Transaction;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case INTEREST = 'interest';
}