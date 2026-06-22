<?php

declare(strict_types=1);

namespace App\Components\Microservice\Moderation\ModerationService;

use Override;

/**
 * Stub when moderation microservice is not configured.
 * Returns empty shadow-ban list (no filtering) and no IP blacklist.
 */
final class NullModerationClient implements ModerationClient
{
    #[Override]
    public function getShadowBanOwnerIds(): array
    {
        return [];
    }

    #[Override]
    public function isIpBlacklisted(string $ipAddress): bool
    {
        return false;
    }
}
