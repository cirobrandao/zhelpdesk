<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ReportService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class ReportController extends BaseController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $service = $this->container->get(ReportService::class);
        $data = $service->dashboardMetrics();

        return $this->view($response, 'reports/index.twig', $data);
    }

    public function exportCsv(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $service = $this->container->get(ReportService::class);
        $csv = $service->exportCsv();

        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="report.csv"');
    }
}
