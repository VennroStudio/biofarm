<?php

declare(strict_types=1);

namespace App\Components\Microservice\Contact\ContactService;

use App\Modules\Contact\Query\GetRelationship\ContactGetRelationshipFetcher;
use App\Modules\Contact\Query\GetRelationship\ContactGetRelationshipQuery;
use App\Modules\Contact\Query\GetUserContactIds\ContactGetUserContactIdsFetcher;
use App\Modules\Contact\Query\GetUserContactIds\ContactGetUserContactIdsQuery;
use App\Modules\Contact\Query\IsContact\ContactIsContactFetcher;
use App\Modules\Contact\Query\IsContact\ContactIsContactQuery;
use Override;

/**
 * Fallback when contact-service is not configured.
 * Delegates to existing local fetchers (in-process queries).
 */
final readonly class NullContactClient implements ContactClient
{
    public function __construct(
        private ContactGetUserContactIdsFetcher $userContactIdsFetcher,
        private ContactIsContactFetcher $isContactFetcher,
        private ContactGetRelationshipFetcher $relationshipFetcher,
    ) {}

    #[Override]
    public function getUserContactIds(int $userId): array
    {
        return $this->userContactIdsFetcher->fetch(
            new ContactGetUserContactIdsQuery(userId: $userId)
        );
    }

    #[Override]
    public function isContact(int $userId, int $contactId): bool
    {
        return $this->isContactFetcher->fetch(
            new ContactIsContactQuery(userId: $userId, contactId: $contactId)
        );
    }

    #[Override]
    public function getRelationship(int $sourceId, int $targetId): int
    {
        return $this->relationshipFetcher->fetch(
            new ContactGetRelationshipQuery(sourceId: $sourceId, targetId: $targetId)
        );
    }
}
