<?php

declare(strict_types=1);

namespace App\Modules\Blog\Entity\BlogPost;

interface BlogPostRepository
{
    public function add(BlogPost $post): void;

    public function remove(BlogPost $post): void;

    public function getById(int $id): BlogPost;

    public function findById(int $id): ?BlogPost;

    public function findBySlug(string $slug): ?BlogPost;
}
