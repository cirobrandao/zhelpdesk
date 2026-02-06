<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getParsedBody()['csrf_token'] ?? $request->getHeaderLine('X-CSRF-Token');
        if (!verify_csrf(is_string($token) ? $token : null)) {
            $response = new Response(403);
            $response->getBody()->write('CSRF validation failed');
            return $response;
        }

        return $handler->handle($request);
    }
}
