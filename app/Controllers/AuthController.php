<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class AuthController extends BaseController
{
    public function showRegister(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $config = $this->container->get('config.app');
        if (empty($config['register_enabled'])) {
            return $response->withStatus(404);
        }
        return $this->view($response, 'auth/register.twig');
    }

    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $config = $this->container->get('config.app');
        if (empty($config['register_enabled'])) {
            return $response->withStatus(404);
        }

        $data = (array) $request->getParsedBody();
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $auth = $this->container->get(AuthService::class);
        $userId = $auth->register($name, $email, $password);
        if (!$userId) {
            return $this->view($response->withStatus(422), 'auth/register.twig', [
                'error' => t('auth.register_failed'),
            ]);
        }

        return $this->redirect('/login');
    }

    public function verifyEmail(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $auth = $this->container->get(AuthService::class);
        $ok = $auth->verifyEmail((string) ($args['token'] ?? ''));
        if ($ok) {
            return $this->redirect('/login');
        }
        $response->getBody()->write('Invalid token');
        return $response->withStatus(400);
    }
    public function showLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->view($response, 'auth/login.twig');
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $auth = $this->container->get(AuthService::class);
        if ($auth->attempt($email, $password)) {
            return $this->redirect('/dashboard');
        }

        return $this->view($response->withStatus(422), 'auth/login.twig', [
            'error' => t('auth.invalid_credentials'),
            'email' => $email,
        ]);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auth = $this->container->get(AuthService::class);
        $auth->logout();
        return $this->redirect('/login');
    }

    public function showForgot(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->view($response, 'auth/forgot.twig');
    }

    public function sendReset(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $email = trim((string) ($data['email'] ?? ''));

        $auth = $this->container->get(AuthService::class);
        $auth->sendResetLink($email);

        return $this->view($response, 'auth/forgot.twig', [
            'status' => t('auth.reset_sent'),
        ]);
    }

    public function showReset(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return $this->view($response, 'auth/reset.twig', [
            'token' => $args['token'] ?? '',
        ]);
    }

    public function resetPassword(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $token = (string) ($args['token'] ?? '');
        $password = (string) ($data['password'] ?? '');

        $auth = $this->container->get(AuthService::class);
        if ($auth->resetPassword($token, $password)) {
            return $this->redirect('/login');
        }

        return $this->view($response->withStatus(422), 'auth/reset.twig', [
            'token' => $token,
            'error' => t('auth.reset_invalid'),
        ]);
    }
}
