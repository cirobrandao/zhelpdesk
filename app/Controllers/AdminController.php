<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\SettingsRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AdminController extends BaseController
{
    public function settings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $repo = $this->container->get(SettingsRepository::class);
        $settings = $repo->all();

        return $this->view($response, 'admin/settings.twig', [
            'settings' => $settings,
        ]);
    }

    public function saveSettings(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $repo = $this->container->get(SettingsRepository::class);
        foreach ($data as $key => $value) {
            $repo->set((string) $key, (string) $value);
        }

        return $this->redirect('/admin/settings');
    }
}
