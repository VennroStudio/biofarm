<?php

declare(strict_types=1);

namespace App\Components\Microservice\Flow\FlowService;

/**
 * Contract for flow operations used by external modules.
 *
 * Current implementation: InProcess (FlowFacadeService in Modules/Flow).
 * After migration to microservice: HttpFlowClient in this package.
 *
 * External modules MUST depend on this interface, not on the concrete implementation.
 */
interface FlowClient
{
    // ── Read (for external modules) ─────────────────────────────────────

    /**
     * Fetch flows by IDs and return them serialized for API responses.
     *
     * @return list<array<array-key, mixed>>
     */
    public function getSerializedFlowsByIds(array $ids): array;

    /**
     * Fetch a single flow's data by ID (for notifications / external consumers).
     *
     * @return array<array-key, mixed>|null
     */
    public function getFlowData(int $id): ?array;
}
