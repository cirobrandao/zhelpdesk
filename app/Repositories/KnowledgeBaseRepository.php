<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class KnowledgeBaseRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listArticles(string $locale, string $search = ''): array
    {
        $sql = "SELECT a.id, COALESCE(t.title, a.title) as title, a.category_id
            FROM kb_articles a
            LEFT JOIN kb_article_translations t ON t.article_id = a.id AND t.locale = :locale
            WHERE 1=1";
        $params = ['locale' => $locale];

        if ($search !== '') {
            $sql .= ' AND (a.title LIKE :q OR t.title LIKE :q OR a.body LIKE :q OR t.body LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY a.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getArticle(int $id, string $locale): ?array
    {
        $stmt = $this->db->prepare("SELECT a.*, COALESCE(t.title, a.title) as title, COALESCE(t.body, a.body) as body
            FROM kb_articles a
            LEFT JOIN kb_article_translations t ON t.article_id = a.id AND t.locale = :locale
            WHERE a.id = :id" );
        $stmt->execute(['id' => $id, 'locale' => $locale]);
        $article = $stmt->fetch();
        return $article ?: null;
    }
}
