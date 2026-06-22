<?php

declare(strict_types=1);

namespace App\Components\Microservice\Moderation\ModerationService;

/**
 * Contract for moderation operations used by the monolith.
 *
 * When MS_MODERATION_HOST is set: HttpModerationClient (calls moderation-service).
 * Otherwise: NullModerationClient (empty shadow-ban list).
 *
 * Complaints, shadowban, ip blacklist: only via moderation microservice.
 */
interface ModerationClient
{
    /**
     * Return owner IDs that are currently shadow-banned (to exclude from feed etc.).
     *
     * @return list<int>
     */
    public function getShadowBanOwnerIds(): array;

    /**
     * Check whether the given IP address is blacklisted (e.g. for signup/restore SMS).
     */
    public function isIpBlacklisted(string $ipAddress): bool;
}
