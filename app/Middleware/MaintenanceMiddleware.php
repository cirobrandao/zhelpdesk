<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class MaintenanceMiddleware implements MiddlewareInterface
{
    private string $flagPath;

    public function __construct(string $flagPath)
    {
        $this->flagPath = $flagPath;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (file_exists($this->flagPath)) {
            $response = new Response(503);
            $response->getBody()->write('Maintenance mode');
            return $response;
        }

        return $handler->handle($request);
    }
}
