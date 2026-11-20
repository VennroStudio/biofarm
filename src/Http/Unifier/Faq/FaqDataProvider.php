<?php

declare(strict_types=1);

namespace App\Http\Unifier\Faq;

use App\Http\View\Faq\FaqItemView;
use App\Modules\Content\Service\MaterialLibrary;

final readonly class FaqDataProvider
{
    public function __construct(private MaterialLibrary $materials) {}

    /** @return list<FaqItemView> */
    public function forPage(string $scope, ?string $pageId = null, ?int $limit = null): array
    {
        return array_map(
            static fn (array $row): FaqItemView => new FaqItemView(
                id: (int)$row['id'],
                question: (string)$row['question'],
                answer: (string)$row['answer'],
            ),
            $this->materials->publicItems('faq', $scope, $pageId, $limit),
        );
    }
}
