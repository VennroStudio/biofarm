<?php

declare(strict_types=1);

namespace App\Components\DuckBug;

use DuckBug\Duck;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Throwable;

final readonly class DuckBugErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private ErrorHandlerInterface $next,
        private Duck $duck
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ): ResponseInterface {
        $this->duck->quack($exception);

        return ($this->next)(
            $request,
            $exception,
            $displayErrorDetails,
            $logErrors,
            $logErrorDetails
        );
    }
}
