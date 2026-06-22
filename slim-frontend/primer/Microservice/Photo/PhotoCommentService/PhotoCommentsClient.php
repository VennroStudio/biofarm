<?php

declare(strict_types=1);

namespace App\Components\Microservice\Photo\PhotoCommentService;

/**
 * Contract for photo-comment operations used by external modules.
 *
 * Current implementation: InProcess (PhotoCommentsFacadeService in Modules/Photo).
 * After migration to microservice: HttpPhotoCommentsClient in this package.
 */
interface PhotoCommentsClient
{
    /**
     * Fetch a single photo comment's data by ID (for notifications / external consumers).
     *
     * @return array<array-key, mixed>|null
     */
    public function getPhotoCommentData(int $id): ?array;
}
