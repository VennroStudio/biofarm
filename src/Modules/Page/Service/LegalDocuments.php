<?php

declare(strict_types=1);

namespace App\Modules\Page\Service;

use Doctrine\DBAL\Connection;

final readonly class LegalDocuments
{
    public const array TITLES = [
        'oferta' => 'Публичная оферта',
        'privacy' => 'Политика обработки персональных данных',
        'personal-data-consent' => 'Согласие на обработку персональных данных',
        'vozvrat' => 'Возврат и обмен товаров',
        'user-agreement' => 'Пользовательское соглашение',
        'marketing-consent' => 'Согласие на получение рекламы',
        'cookie-policy' => 'Политика использования файлов cookie',
    ];

    public function __construct(private Connection $db) {}

    public static function defaultContent(string $slug): string
    {
        $html = file_get_contents(dirname(__DIR__, 4) . '/templates/pages/legal/documents/' . $slug . '.html.twig');
        if ($html === false) {
            throw new \RuntimeException('Missing legal document: ' . $slug);
        }
        return $html;
    }

    public function content(string $slug): string
    {
        $html = $this->db->fetchOne(
            "SELECT content FROM pages WHERE system_key = ? AND page_type = 'system' AND deleted_at IS NULL",
            [$slug],
        );

        return is_string($html) && trim($html) !== '' ? $html : self::defaultContent($slug);
    }

    public function remember(string $slug, string $html): void
    {
        $this->db->executeStatement(
            'INSERT INTO legal_document_revisions (slug, digest, content, created_at)
             VALUES (?, ?, ?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE digest = VALUES(digest)',
            [$slug, hash('sha256', $html), $html],
        );
    }
}
