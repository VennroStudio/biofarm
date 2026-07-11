<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Auth;

use App\Components\Exception\AccessDeniedException;
use App\Components\Http\Cookie\CookieContext;
use App\Components\Http\Cookie\CookieManager;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Modules\User\Command\Auth\Login\LoginCommand;
use App\Modules\User\Command\Auth\Login\LoginHandler;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Query\User\FindByEmail\UserFindByEmailFetcher;
use App\Modules\User\Query\User\FindByEmail\UserFindByEmailQuery;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;
use JsonException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

final readonly class LoginAction implements RequestHandlerInterface
{
    public function __construct(
        private Denormalizer $denormalizer,
        private Validator $validator,
        private LoginHandler $handler,
        private UserFindByEmailFetcher $userFetcher,
        private CookieManager $cookieManager,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     * @throws ExceptionInterface
     * @throws JsonException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->denormalizer->denormalize((array)$request->getParsedBody(), LoginCommand::class);
        $this->validator->validate($command);

        $result = $this->handler->handle($command);
        $user = $this->userFetcher->fetchNotDeleted(new UserFindByEmailQuery(mb_strtolower($command->email)));

        if ($user === null || !\in_array($user->role, [UserRole::ADMIN, UserRole::DEVELOPER, UserRole::EDITOR], true)) {
            throw new AccessDeniedException();
        }

        $response = new JsonDataResponse([
            'access_token' => $result->accessToken,
            'expires_in'   => $result->expiresIn,
            'admin'        => [
                'id'         => $user->id,
                'email'      => $user->email,
                'first_name' => $user->firstName,
                'role'       => [
                    'id'    => $user->role->value,
                    'label' => $user->role->getLabel(),
                ],
            ],
        ]);

        return $this->cookieManager->apply(
            response: $response,
            context: new CookieContext(
                refreshToken: $result->refreshToken,
                loggedIn: '1',
            ),
        );
    }
}
