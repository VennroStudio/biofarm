<?php

declare(strict_types=1);

namespace App\Components\Microservice\Flow\FlowService;

/**
 * Contract for flow counters used by external modules.
 *
 * NOTE: for now counters are served from monolith DB (FlowFacadeService).
 * When flows fully move to microservice, either:
 * - add explicit count endpoints to flows-service and provide Http implementation, or
 * - change counter update workflow to be event-driven.
 */
interface FlowCounterClient
{
    public function countFlowsByUser(int $userId): int;

    public function countFlowsByUnion(int $unionId): int;
}
