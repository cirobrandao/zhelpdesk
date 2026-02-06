<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TicketService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TicketController extends BaseController
{
    public function dashboard(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $service = $this->container->get(TicketService::class);
        $stats = $service->statusCounters();
        return $this->view($response, 'dashboard.twig', [
            'stats' => $stats,
        ]);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $filters = $request->getQueryParams();
        $service = $this->container->get(TicketService::class);
        $tickets = $service->listTickets($filters);

        return $this->view($response, 'tickets/index.twig', [
            'tickets' => $tickets,
            'filters' => $filters,
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $service = $this->container->get(TicketService::class);
        return $this->view($response, 'tickets/create.twig', [
            'categories' => $service->categories(),
            'priorities' => $service->priorities(),
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $files = $request->getUploadedFiles();

        $service = $this->container->get(TicketService::class);
        $ticketId = $service->createTicket($data, (int) $_SESSION['user_id'], $files);

        return $this->redirect('/tickets/' . $ticketId);
    }

    public function view(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $service = $this->container->get(TicketService::class);
        $ticket = $service->getTicket((int) $args['id']);
        if (!$ticket) {
            $response->getBody()->write('Ticket not found');
            return $response->withStatus(404);
        }

        $kbService = $this->container->get(\App\Services\KnowledgeBaseService::class);
        $related = $kbService->listArticles(['q' => $ticket['subject'] ?? '']);

        return $this->view($response, 'tickets/view.twig', array_merge($ticket, [
            'related_articles' => $related,
        ]));
    }

    public function reply(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $files = $request->getUploadedFiles();

        $service = $this->container->get(TicketService::class);
        $service->replyToTicket((int) $args['id'], (int) $_SESSION['user_id'], $data, $files);

        return $this->redirect('/tickets/' . $args['id']);
    }

    public function close(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $service = $this->container->get(TicketService::class);
        $service->closeTicket((int) $args['id'], (int) $_SESSION['user_id']);
        return $this->redirect('/tickets/' . $args['id']);
    }

    public function reopen(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $service = $this->container->get(TicketService::class);
        $service->reopenTicket((int) $args['id'], (int) $_SESSION['user_id']);
        return $this->redirect('/tickets/' . $args['id']);
    }
}
