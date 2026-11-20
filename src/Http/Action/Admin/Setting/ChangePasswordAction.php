<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Setting;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Service\PasswordHasherService;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ChangePasswordAction implements RequestHandlerInterface
{
    private const int MIN_PASSWORD_LENGTH = 4;

    public function __construct(
        private UserRepository $userRepository,
        private PasswordHasherService $passwordHasher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $payload = (array)$request->getParsedBody();
        $currentPassword = trim((string)($payload['current_password'] ?? ''));
        $newPassword = (string)($payload['new_password'] ?? '');
        $confirmPassword = (string)($payload['confirm_password'] ?? '');

        if ($currentPassword === '') {
            throw new DomainExceptionModule('user', 'error.current_password_required', 31, status: 422);
        }

        if (mb_strlen($newPassword) < self::MIN_PASSWORD_LENGTH) {
            throw new DomainExceptionModule('user', 'error.new_password_too_short', 32, status: 422);
        }

        if ($newPassword !== $confirmPassword) {
            throw new DomainExceptionModule('user', 'error.password_confirmation_mismatch', 33, status: 422);
        }

        $user = $this->userRepository->getById($identity->id);
        if (!$this->passwordHasher->verify($currentPassword, $user->password)) {
            throw new DomainExceptionModule('user', 'error.current_password_invalid', 34);
        }

        $user->setPassword($this->passwordHasher->hash($newPassword));
        $this->flusher->flush();

        return new JsonDataSuccessResponse(1, 200);
    }
}
