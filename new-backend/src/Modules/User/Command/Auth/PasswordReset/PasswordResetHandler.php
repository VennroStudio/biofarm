<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Auth\PasswordReset;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use App\Modules\User\Command\Mailer\PasswordReset\PasswordResetCommand as MailerCommand;
use App\Modules\User\Command\Mailer\PasswordReset\PasswordResetHandler as MailerHandler;
use App\Modules\User\Command\UserToken\Create\CreateUserTokenCommand;
use App\Modules\User\Command\UserToken\Create\CreateUserTokenHandler;
use App\Modules\User\Entity\User\Fields\Enums\UserStatus;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Query\User\FindByEmail\UserFindByEmailFetcher;
use App\Modules\User\Query\User\FindByEmail\UserFindByEmailQuery;
use App\Modules\User\ReadModel\User\UserByEmail;
use App\Modules\User\Service\TokenHasherService;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;
use Random\RandomException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class PasswordResetHandler
{
    public function __construct(
        private UserFindByEmailFetcher $userByEmailFetcher,
        private CreateUserTokenHandler $createUserTokenHandler,
        private TokenHasherService $tokenHasher,
        private MailerHandler $mailerHandler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     * @throws RandomException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function handle(PasswordResetCommand $command): void
    {
        $user = $this->getUser($command->email);

        $plainToken = $this->tokenHasher->generate();

        $this->saveResetToken($user, $plainToken);

        $this->sendEmail($user, $plainToken, $command->locale);
    }

    /**
     * @throws Exception
     */
    private function getUser(string $email): UserByEmail
    {
        $email = mb_strtolower($email);
        $user = $this->userByEmailFetcher->fetchNotDeleted(new UserFindByEmailQuery($email));

        if ($user === null || $user->status !== UserStatus::ACTIVE) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.user_not_found',
                code: 5
            );
        }

        return $user;
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    private function saveResetToken(UserByEmail $user, string $plainToken): void
    {
        $this->createUserTokenHandler->handle(new CreateUserTokenCommand(
            userId: $user->id,
            type: UserTokenType::PASSWORD_RESET,
            tokenHash: $this->tokenHasher->hash($plainToken),
            expiresAt: UtcClock::fromString('+2 hours'),
        ));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function sendEmail(UserByEmail $user, string $plainToken, string $locale): void
    {
        $this->mailerHandler->handle(new MailerCommand(
            email: $user->email,
            token: $plainToken,
            locale: $locale,
        ));
    }
}
