<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TicketService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiController extends BaseController
{
    public function health(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, ['status' => 'ok']);
    }

    public function tickets(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $service = $this->container->get(TicketService::class);
        $tickets = $service->listTickets($request->getQueryParams());
        return $this->json($response, ['data' => $tickets]);
    }

    public function ticket(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $service = $this->container->get(TicketService::class);
        $ticket = $service->getTicket((int) $args['id']);
        if (!$ticket) {
            return $this->json($response, ['error' => 'Not found'], 404);
        }
        return $this->json($response, ['data' => $ticket]);
    }

    public function reply(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $user = $request->getAttribute('user');

        $service = $this->container->get(TicketService::class);
        $service->replyToTicket((int) $args['id'], (int) $user['id'], $data, []);

        return $this->json($response, ['status' => 'ok']);
    }
}
