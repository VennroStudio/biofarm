<?php

declare(strict_types=1);

namespace App\Components\Microservice\Flow\FlowCommentService;

/**
 * Contract for flow-comment operations used by external modules.
 *
 * Current implementation: InProcess (FlowCommentsFacadeService in Modules/Flow).
 * After migration to microservice: HttpFlowCommentsClient in this package.
 *
 * External modules MUST depend on this interface, not on the concrete implementation.
 */
interface FlowCommentsClient
{
    /**
     * Fetch a single flow comment's data by ID (for notifications / external consumers).
     *
     * @return array<array-key, mixed>|null
     */
    public function getFlowCommentData(int $id): ?array;
}
