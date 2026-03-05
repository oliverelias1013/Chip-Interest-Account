<?php

declare(strict_types=1);

namespace Chip\Stats;

class StatsClient implements StatsClientInterface
{
    public function __construct(
        private readonly string $baseUrl = 'https://stats.dev.chip.test'
    ) {}

    public function getMonthlyIncome(string $userId): ?int
    {
        $url = rtrim($this->baseUrl, '/') . "/users/{$userId}";

        $context = stream_context_create([
            'http' => ['ignore_errors' => true]
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            throw new StatsApiException("Could not reach Stats API for user: {$userId}");
        }

        $status = $this->parseStatusCode($http_response_header ?? []);
        if ($status !== 200) {
            throw new StatsApiException("Stats API returned status {$status} for user: {$userId}");
        }

        $data = json_decode($body, true);

        if (!isset($data['id'])) {
            throw new StatsApiException("Unexpected response format from Stats API.");
        }

        return $data['income'] ?? null;
    }

    private function parseStatusCode(array $headers): int
    {
        if (empty($headers)) {
            return 0;
        }
        preg_match('/HTTP\/\d\.\d (\d+)/', $headers[0], $matches);
        return isset($matches[1]) ? (int) $matches[1] : 0;
    }
}