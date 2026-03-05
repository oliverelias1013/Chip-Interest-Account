<?php

declare(strict_types=1);

namespace Chip\Interest;

class InterestCalculator
{
    private const INCOME_THRESHOLD_PENNIES = 500000;

    private const RATE_UNKNOWN = 0.005;
    private const RATE_BELOW   = 0.0093;
    private const RATE_ABOVE   = 0.0102;

    public function determineRate(?int $monthlyIncomePennies): float
    {
        if ($monthlyIncomePennies === null) {
            return self::RATE_UNKNOWN;
        }

        return $monthlyIncomePennies >= self::INCOME_THRESHOLD_PENNIES
            ? self::RATE_ABOVE
            : self::RATE_BELOW;
    }

    public function calculateForThreeDays(int $balancePennies, float $yearlyRate): float
    {
        return $balancePennies * $yearlyRate * (3 / 365);
    }
}