<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;
use Slim\Views\Twig;

abstract class BaseController
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function view(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        $twig = $this->container->get(Twig::class);
        $auth = $this->container->get(AuthService::class);
        $data['currentUser'] = $auth->currentUser();
        $data['csrf_token'] = csrf_token();
        return $twig->render($response, $template, $data);
    }

    protected function redirect(string $path): ResponseInterface
    {
        $response = new Response(302);
        return $response->withHeader('Location', $path);
    }

    protected function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}
