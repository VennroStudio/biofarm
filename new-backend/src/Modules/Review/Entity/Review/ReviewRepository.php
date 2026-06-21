<?php

declare(strict_types=1);

namespace App\Modules\Review\Entity\Review;

interface ReviewRepository
{
    public function add(Review $review): void;

    public function remove(Review $review): void;

    public function getById(string $id): Review;

    public function findById(string $id): ?Review;
}
