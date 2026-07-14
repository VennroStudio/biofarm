<?php

declare(strict_types=1);

namespace App\Modules\Page\Service;

use App\Components\Exception\DomainExceptionModule;

final readonly class PageTemplateCatalog
{
    private const array TEMPLATES = [
        'basic' => [
            'label'       => 'Обычная страница',
            'description' => 'Контентная SEO-страница с заголовком и текстом.',
            'template'    => 'pages/content/basic.html.twig',
        ],
        'legal' => [
            'label'       => 'Юридическая страница',
            'description' => 'Узкая текстовая страница для документов и правил.',
            'template'    => 'pages/content/legal.html.twig',
        ],
        'landing' => [
            'label'       => 'Посадочная страница',
            'description' => 'Простая страница с крупным вступительным блоком и контентом.',
            'template'    => 'pages/content/landing.html.twig',
        ],
    ];

    /**
     * @return list<array{key: string, label: string, description: string}>
     */
    public function all(): array
    {
        $items = [];

        foreach (self::TEMPLATES as $key => $template) {
            $items[] = [
                'key'         => $key,
                'label'       => $template['label'],
                'description' => $template['description'],
            ];
        }

        return $items;
    }

    public function assertExists(string $key): void
    {
        if (!\array_key_exists($key, self::TEMPLATES)) {
            throw new DomainExceptionModule('page', 'error.page_template_not_found', 5);
        }
    }

    public function twigTemplate(string $key): string
    {
        $this->assertExists($key);

        return self::TEMPLATES[$key]['template'];
    }
}
