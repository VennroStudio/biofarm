<?php

declare(strict_types=1);

namespace App\Components\Microservice\Contact\ContactService;

/**
 * Contract for contact read operations used by the monolith internally.
 *
 * When MS_CONTACT_HOST is set: HttpContactClient (calls contact-service).
 * Otherwise: NullContactClient (uses existing local fetchers).
 *
 * User-facing HTTP endpoints are routed directly to contact-service via nginx.
 */
interface ContactClient
{
    /**
     * @return list<int>
     */
    public function getUserContactIds(int $userId): array;

    public function isContact(int $userId, int $contactId): bool;

    /**
     * @return int Relationship status (0=none, 1=requestIn, 2=requestOut, 3=refused, 4=contact)
     */
    public function getRelationship(int $sourceId, int $targetId): int;
}
