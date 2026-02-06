<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repositories\UserRepository;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class ApiAuthMiddleware implements MiddlewareInterface
{
    private UserRepository $users;

    public function __construct(ContainerInterface $container)
    {
        $this->users = $container->get(UserRepository::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $request->getHeaderLine('Authorization');
        $token = '';
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = trim($matches[1]);
        }

        if ($token === '') {
            $response = new Response(401);
            $response->getBody()->write('Unauthorized');
            return $response;
        }

        $user = $this->users->findByApiToken($token);
        if (!$user) {
            $response = new Response(401);
            $response->getBody()->write('Unauthorized');
            return $response;
        }

        return $handler->handle($request->withAttribute('user', $user));
    }
}
