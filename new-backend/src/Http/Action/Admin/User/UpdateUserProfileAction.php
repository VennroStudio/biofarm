<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\User;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransactionRepository;
use App\Modules\Program\Service\ProgramService;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserProfile\UserProfile;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use DateMalformedStringException;
use DomainException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateUserProfileAction implements RequestHandlerInterface
{
    public function __construct(
        private ProgramService $program,
        private UserRepository $userRepository,
        private UserProfileRepository $profileRepository,
        private BonusTransactionRepository $bonusTransactionRepository,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $userId = Route::getArgumentToInt($request, 'id');
        $payload = (array)$request->getParsedBody();
        $user = $this->userRepository->getById($userId);

        if (isset($payload['firstName']) || isset($payload['first_name']) || isset($payload['lastName']) || isset($payload['last_name'])) {
            $user->edit(
                (string)$this->payloadValue($payload, 'lastName', 'last_name', $user->lastName),
                (string)$this->payloadValue($payload, 'firstName', 'first_name', $user->firstName),
            );
        }

        $profile = $this->profileRepository->findByUserId($userId);
        if ($profile === null) {
            $profile = UserProfile::create($userId);
            $this->profileRepository->add($profile);
        }

        $referralCode = $this->nullableString($this->payloadValue($payload, 'referralCode', 'referral_code', $profile->referralCode));
        if ($referralCode !== null) {
            $profileByCode = $this->profileRepository->findByReferralCode($referralCode);
            if ($profileByCode !== null && $profileByCode->userId !== $userId) {
                throw new DomainExceptionModule('user', 'error.referral_code_already_used', 38, status: 422);
            }
        }

        $referredByUserId = $this->nullableInt($this->payloadValue($payload, 'referredByUserId', 'referred_by_user_id', $profile->referredByUserId));
        if ($referredByUserId !== null) {
            if ($referredByUserId === $userId || $this->userRepository->findById($referredByUserId) === null) {
                throw new DomainExceptionModule('user', 'error.invalid_referrer', 39, status: 422);
            }
        }

        $partner = (bool)$this->payloadValue($payload, 'isPartner', 'is_partner', $profile->isPartner);
        $treeChanged = $partner !== $profile->isPartner || $referredByUserId !== $profile->referredByUserId;
        if ($treeChanged && trim((string)($payload['reason'] ?? '')) === '') {
            throw new DomainException('Reason required for tree changes');
        }
        $profile->edit(
            phone: $this->nullableString($this->payloadValue($payload, 'phone', 'phone', $profile->phone)),
            cardNumber: $this->nullableString($this->payloadValue($payload, 'cardNumber', 'card_number', $profile->cardNumber)),
            isPartner: $profile->isPartner,
            referralCode: $referralCode,
            referredByUserId: $profile->referredByUserId,
        );

        if (\array_key_exists('bonusBalance', $payload) || \array_key_exists('bonus_balance', $payload) || !empty($payload['bonusAdjustment']) || !empty($payload['bonus_adjustment'])) {
            throw new DomainExceptionModule('program', 'Use audited program adjustments', 1, status: 409);
        }

        $this->program->atomic(function () use ($treeChanged, $userId, $referredByUserId, $partner, $request, $payload): void {
            $this->flusher->flush();
            if ($treeChanged) {
                $this->program->changeTree($userId, $referredByUserId, $partner, RequestIdentity::get($request)->id, (string)$payload['reason']);
            }
        });

        return new JsonDataSuccessResponse(1, 200);
    }

    /**
     * @param array<array-key, mixed> $payload
     */
    private function payloadValue(array $payload, string $firstKey, string $secondKey, bool|float|int|string|null $fallback): bool|float|int|string|null
    {
        if (\array_key_exists($firstKey, $payload)) {
            return $this->scalarOrNull($payload[$firstKey]);
        }

        if (\array_key_exists($secondKey, $payload)) {
            return $this->scalarOrNull($payload[$secondKey]);
        }

        return $fallback;
    }

    private function scalarOrNull(mixed $value): bool|float|int|string|null
    {
        return \is_scalar($value) || $value === null ? $value : null;
    }

    private function nullableString(bool|float|int|string|null $value): ?string
    {
        $value = \is_scalar($value) ? trim((string)$value) : '';

        return $value !== '' ? $value : null;
    }

    private function nullableInt(bool|float|int|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }
}
