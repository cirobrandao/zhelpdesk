<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TicketRepository;

class ReportService
{
    private TicketRepository $tickets;

    public function __construct(TicketRepository $tickets)
    {
        $this->tickets = $tickets;
    }

    public function dashboardMetrics(): array
    {
        $counters = $this->tickets->statusCounters();
        $avgFirstResponse = $this->tickets->averageResponseTime();
        $avgResolution = $this->tickets->averageResolutionTime();

        return [
            'counters' => $counters,
            'avg_first_response' => $avgFirstResponse,
            'avg_resolution' => $avgResolution,
        ];
    }

    public function exportCsv(): string
    {
        $metrics = $this->dashboardMetrics();
        $lines = [
            'metric,value',
        ];

        foreach ($metrics['counters'] as $row) {
            $lines[] = sprintf('status_%s,%s', $row['status'], $row['total']);
        }
        $lines[] = sprintf('avg_first_response_seconds,%s', $metrics['avg_first_response'] ?? '');
        $lines[] = sprintf('avg_resolution_seconds,%s', $metrics['avg_resolution'] ?? '');

        return implode("\n", $lines) . "\n";
    }
}
