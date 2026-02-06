<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\KnowledgeBaseRepository;

class KnowledgeBaseService
{
    private KnowledgeBaseRepository $repo;

    public function __construct(KnowledgeBaseRepository $repo)
    {
        $this->repo = $repo;
    }

    public function listArticles(array $filters): array
    {
        $locale = $_SESSION['locale'] ?? 'pt_BR';
        $search = (string) ($filters['q'] ?? '');
        return $this->repo->listArticles($locale, $search);
    }

    public function getArticle(int $id, ?string $locale): array
    {
        $loc = $locale ?: ($_SESSION['locale'] ?? 'pt_BR');
        return $this->repo->getArticle($id, $loc) ?: [];
    }
}
