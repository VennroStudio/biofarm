<?php

declare(strict_types=1);

use App\Components\Auth\JwtTokenService;
use App\Components\Http\Middleware\Identity\Authenticate;
use App\Modules\User\Query\User\GetById\UserGetByIdFetcher;
use Psr\Container\ContainerInterface;

use function App\Components\env;

return [
    JwtTokenService::class => static function (): JwtTokenService {
        $secret = env('JWT_SECRET', '');

        if ($secret === '') {
            throw new LogicException('JWT_SECRET must be set in environment.');
        }

        return new JwtTokenService($secret);
    },

    Authenticate::class => static function (ContainerInterface $container): Authenticate {
        /** @var JwtTokenService $jwtTokenService */
        $jwtTokenService = $container->get(JwtTokenService::class);

        /** @var UserGetByIdFetcher $userGetByIdFetcher */
        $userGetByIdFetcher = $container->get(UserGetByIdFetcher::class);

        return new Authenticate($jwtTokenService, $userGetByIdFetcher);
    },
];
