<?php

declare(strict_types=1);

namespace App\Http\View\Faq;

final readonly class FaqItemView
{
    public function __construct(
        public int $id,
        public string $question,
        public string $answer,
    ) {}
}
