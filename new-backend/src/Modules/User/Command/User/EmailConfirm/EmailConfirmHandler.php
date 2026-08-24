<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\EmailConfirm;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\Setting\SiteSettings;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransaction;
use App\Modules\Bonus\Entity\BonusTransaction\BonusTransactionRepository;
use App\Modules\Bonus\Entity\BonusTransaction\Fields\Enums\BonusTransactionType;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserProfile\UserProfile;
use App\Modules\User\Entity\UserProfile\UserProfileRepository;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Entity\UserToken\UserToken;
use App\Modules\User\Entity\UserToken\UserTokenRepository;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashFetcher;
use App\Modules\User\Query\UserToken\FindByHash\UserTokenFindByHashQuery;
use App\Modules\User\Service\TokenHasherService;
use DateMalformedStringException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class EmailConfirmHandler
{
    public function __construct(
        private TokenHasherService $tokenHasher,
        private UserTokenFindByHashFetcher $userTokenByHashFetcher,
        private UserTokenRepository $userTokenRepository,
        private UserRepository $userRepository,
        private FlusherInterface $flusher,
        private Cacher $cacher,
        private SiteSettings $settings,
        private UserProfileRepository $profileRepository,
        private BonusTransactionRepository $bonusRepository,
        private Connection $connection,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function handle(EmailConfirmCommand $command): void
    {
        $userToken = $this->findValidToken($command->token);
        $user = $this->userRepository->getById($userToken->userId);

        $user->activate();
        $this->applyWelcomeBonus((int)$user->id);

        $this->cacher->delete('user_identity_' . $user->id);

        $this->flusher->flush();
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    private function applyWelcomeBonus(int $userId): void
    {
        $amount = $this->settings->int('welcome_bonus_amount');
        if (!$this->settings->bool('welcome_bonus_enabled') || $amount <= 0) {
            return;
        }

        $alreadyApplied = (int)$this->connection->fetchOne(
            'SELECT COUNT(*) FROM bonus_transactions WHERE user_id = :user_id AND type = :type',
            ['user_id' => $userId, 'type' => BonusTransactionType::WELCOME_BONUS->value],
        ) > 0;

        if ($alreadyApplied) {
            return;
        }

        $profile = $this->profileRepository->findByUserId($userId);
        if ($profile === null) {
            $profile = UserProfile::create(
                userId: $userId,
                referralCode: 'bf-' . $userId,
            );
            $this->profileRepository->add($profile);
        }

        $profile->addBonus($amount);
        $this->bonusRepository->add(BonusTransaction::create(
            userId: $userId,
            amount: $amount,
            type: BonusTransactionType::WELCOME_BONUS,
            comment: 'Welcome-бонус за подтверждение email',
        ));
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    private function findValidToken(string $plainToken): UserToken
    {
        $tokenHash = $this->tokenHasher->hash($plainToken);
        $tokenByHash = $this->userTokenByHashFetcher->fetch(
            new UserTokenFindByHashQuery($tokenHash, UserTokenType::EMAIL_VERIFICATION)
        );

        if ($tokenByHash === null) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.invalid_or_expired_confirm_link',
                code: 7
            );
        }

        $userToken = $this->userTokenRepository->getById($tokenByHash->id);

        if (!$userToken->markUsed()) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.invalid_or_expired_confirm_link',
                code: 7
            );
        }

        $this->cacher->delete('user_token_' . $userToken->tokenHash);

        return $userToken;
    }
}
