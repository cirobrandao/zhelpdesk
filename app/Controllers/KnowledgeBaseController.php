<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\KnowledgeBaseService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class KnowledgeBaseController extends BaseController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $service = $this->container->get(KnowledgeBaseService::class);
        $articles = $service->listArticles($request->getQueryParams());

        return $this->view($response, 'kb/index.twig', [
            'articles' => $articles,
        ]);
    }

    public function view(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $service = $this->container->get(KnowledgeBaseService::class);
        $article = $service->getArticle((int) $args['id'], $request->getAttribute('locale'));

        return $this->view($response, 'kb/view.twig', [
            'article' => $article,
        ]);
    }
}
