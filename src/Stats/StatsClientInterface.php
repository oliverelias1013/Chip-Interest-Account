<?php

declare(strict_types=1);

namespace Chip\Stats;

interface StatsClientInterface
{
    /**
     * Returns monthly income in pennies, or null if not known.
     * @throws StatsApiException on non-200 response
     */
    public function getMonthlyIncome(string $userId): ?int;
}