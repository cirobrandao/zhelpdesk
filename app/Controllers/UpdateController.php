<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UpdateService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateController extends BaseController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $service = $this->container->get(UpdateService::class);
        $status = $service->currentStatus();

        return $this->view($response, 'admin/updates.twig', [
            'status' => $status,
        ]);
    }

    public function check(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $service = $this->container->get(UpdateService::class);
        $result = $service->checkForUpdates();

        return $this->view($response, 'admin/updates.twig', [
            'status' => $service->currentStatus(),
            'check' => $result,
        ]);
    }

    public function download(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $service = $this->container->get(UpdateService::class);
        $result = $service->downloadAndVerify();

        return $this->view($response, 'admin/updates.twig', [
            'status' => $service->currentStatus(),
            'download' => $result,
        ]);
    }
}
