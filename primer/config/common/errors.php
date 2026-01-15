<?php

declare(strict_types=1);

use App\Components\DuckBug\DuckBugErrorHandler;
use DuckBug\Duck;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Middleware\ErrorMiddleware;
use ZayMedia\Shared\Components\ErrorHandler\LogErrorHandler;

use function App\Components\env;

return [
    ErrorMiddleware::class => static function (ContainerInterface $container): ErrorMiddleware {
        $callableResolver = $container->get(CallableResolverInterface::class);
        $responseFactory = $container->get(ResponseFactoryInterface::class);
        /**
         * @psalm-suppress MixedArrayAccess
         * @var array{display_details:bool} $config
         */
        $config = $container->get('config')['errors'];

        $middleware = new ErrorMiddleware(
            $callableResolver,
            $responseFactory,
            $config['display_details'],
            true,
            true
        );

        $logger = $container->get(LoggerInterface::class);

        $middleware->setDefaultErrorHandler(
            new DuckBugErrorHandler(
                new LogErrorHandler($callableResolver, $responseFactory, $logger),
                $container->get(Duck::class)
            )
        );

        return $middleware;
    },

    'config' => [
        'errors' => [
            'display_details' => (bool)env('APP_DEBUG', '0'),
        ],
    ],
];
