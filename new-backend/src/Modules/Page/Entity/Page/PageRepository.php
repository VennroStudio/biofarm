<?php

declare(strict_types=1);

namespace App\Modules\Page\Entity\Page;

interface PageRepository
{
    public function add(Page $page): void;

    public function getById(int $id): Page;

    public function findById(int $id): ?Page;

    public function findBySlugPath(string $slugPath): ?Page;

    public function findBySystemKey(string $systemKey): ?Page;
}
