<?php

declare(strict_types=1);

use App\Components\Http\HttpErrorHandler;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Middleware\ErrorMiddleware;

use function App\Components\env;

return [
    ErrorMiddleware::class => static function (ContainerInterface $container): ErrorMiddleware {
        /** @var array{errors: array{display_details: bool}} $fullConfig */
        $fullConfig = $container->get('config');
        $config = $fullConfig['errors'];

        /** @var CallableResolverInterface $callableResolver */
        $callableResolver = $container->get(CallableResolverInterface::class);

        /** @var ResponseFactoryInterface $responseFactory */
        $responseFactory = $container->get(ResponseFactoryInterface::class);

        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);

        $middleware = new ErrorMiddleware(
            callableResolver: $callableResolver,
            responseFactory: $responseFactory,
            displayErrorDetails: $config['display_details'],
            logErrors: true,
            logErrorDetails: true,
        );

        $middleware->setDefaultErrorHandler(
            new HttpErrorHandler($callableResolver, $responseFactory, $logger),
        );

        return $middleware;
    },

    'config' => [
        'errors' => [
            'display_details' => (bool)env('APP_DEBUG'),
        ],
    ],
];
