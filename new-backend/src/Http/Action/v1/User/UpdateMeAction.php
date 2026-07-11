<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserProfile\UserProfile;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateMeAction implements RequestHandlerInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserProfileRepository $profileRepository,
        private FlusherInterface $flusher,
        private Cacher $cacher,
        private GetMeAction $getMeAction,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $payload = (array)$request->getParsedBody();
        $user = $this->userRepository->getById($identity->id);

        $name = $this->stringValue($payload, 'name');
        if ($name !== null) {
            [$firstName, $lastName] = $this->splitName($name, $user->firstName, $user->lastName);
            $user->edit($lastName, $firstName);
        }

        $profile = $this->profileRepository->findByUserId($identity->id);
        if ($profile === null) {
            $profile = UserProfile::create(
                userId: $identity->id,
                referralCode: 'bf-' . $identity->id,
            );
            $this->profileRepository->add($profile);
        }

        $profile->edit(
            phone: $this->stringValue($payload, 'phone') ?? $profile->phone,
            cardNumber: $this->stringValue($payload, 'cardNumber', 'card_number') ?? $profile->cardNumber,
            isPartner: $profile->isPartner,
            referralCode: $profile->referralCode ?? 'bf-' . $identity->id,
            referredByUserId: $profile->referredByUserId,
        );

        $this->flusher->flush();
        $this->cacher->delete('user_identity_' . $identity->id);

        return $this->getMeAction->handle($request);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function stringValue(array $payload, string $firstKey, ?string $secondKey = null): ?string
    {
        $keys = $secondKey === null ? [$firstKey] : [$firstKey, $secondKey];
        foreach ($keys as $key) {
            if (!\array_key_exists($key, $payload) || !\is_scalar($payload[$key])) {
                continue;
            }

            $value = trim((string)$payload[$key]);

            return $value !== '' ? $value : null;
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name, string $fallbackFirstName, string $fallbackLastName): array
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $firstName = (string)($parts[0] ?? $fallbackFirstName);
        $lastName = trim(implode(' ', \array_slice($parts, 1)));

        return [
            $firstName !== '' ? $firstName : $fallbackFirstName,
            $lastName !== '' ? $lastName : $fallbackLastName,
        ];
    }
}
