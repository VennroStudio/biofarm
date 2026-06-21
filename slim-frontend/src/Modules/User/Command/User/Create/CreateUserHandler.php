<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\Create;

use App\Components\Clock\UtcClock;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Modules\User\Command\Mailer\EmailVerification\EmailVerificationCommand;
use App\Modules\User\Command\Mailer\EmailVerification\EmailVerificationHandler;
use App\Modules\User\Command\UserToken\Create\CreateUserTokenCommand;
use App\Modules\User\Command\UserToken\Create\CreateUserTokenHandler;
use App\Modules\User\Entity\User\User;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserToken\Fields\Enums\UserTokenType;
use App\Modules\User\Query\User\FindByEmail\UserFindByEmailFetcher;
use App\Modules\User\Query\User\FindByEmail\UserFindByEmailQuery;
use App\Modules\User\Service\PasswordHasherService;
use App\Modules\User\Service\TokenHasherService;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;
use Random\RandomException;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private CreateUserTokenHandler $createUserTokenHandler,
        private UserFindByEmailFetcher $userByEmailFetcher,
        private PasswordHasherService $passwordHasher,
        private TokenHasherService $tokenHasher,
        private FlusherInterface $flusher,
        private EmailVerificationHandler $emailVerificationHandler,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function handle(CreateUserCommand $command): void
    {
        $email = mb_strtolower($command->email);

        $this->assertEmailNotRegistered($email);

        $user = $this->createUser($command, $email);

        $plainToken = $this->createEmailVerificationToken((int)$user->id);
        $this->sendVerificationEmail($user, $plainToken, $command->locale);
    }

    /**
     * @throws Exception
     */
    private function assertEmailNotRegistered(string $email): void
    {
        if ($this->userByEmailFetcher->fetchAny(new UserFindByEmailQuery($email)) !== null) {
            throw new DomainExceptionModule(
                module: 'user',
                message: 'error.email_already_registered',
                code: 8
            );
        }
    }

    /**
     * @throws DateMalformedStringException
     */
    private function createUser(CreateUserCommand $command, string $email): User
    {
        $user = User::create(
            lastName: $this->normalizeName($command->lastName),
            firstName: $this->normalizeName($command->firstName),
            email: $email,
            password: $this->passwordHasher->hash($command->password),
        );

        $this->userRepository->add($user);
        $this->flusher->flush();

        if ($user->id === null) {
            throw new RuntimeException('User ID is null after creation.');
        }

        return $user;
    }

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    private function createEmailVerificationToken(int $userId): string
    {
        $plainToken = $this->tokenHasher->generate();

        $this->createUserTokenHandler->handle(new CreateUserTokenCommand(
            userId: $userId,
            type: UserTokenType::EMAIL_VERIFICATION,
            tokenHash: $this->tokenHasher->hash($plainToken),
            expiresAt: UtcClock::fromString('+24 hours'),
        ));

        return $plainToken;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function sendVerificationEmail(User $user, string $plainToken, string $locale): void
    {
        $this->emailVerificationHandler->handle(new EmailVerificationCommand(
            email: $user->email,
            firstName: $user->firstName,
            token: $plainToken,
            locale: $locale,
        ));
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }
}
