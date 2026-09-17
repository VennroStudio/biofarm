<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\User;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\User\Service\AdminUserDetails;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetUserDetailsAction implements RequestHandlerInterface
{
    public function __construct(private AdminUserDetails $details) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        return new JsonDataResponse($this->details->get(
            Route::getArgumentToInt($request, 'id'),
            (string)($query['section'] ?? 'profile'),
            (int)($query['page'] ?? 1),
        ));
    }
}
