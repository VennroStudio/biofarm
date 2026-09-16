<?php

declare(strict_types=1);

namespace App\Http\Action\v1\PartnerOffer;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\PartnerOffer\Service\OfferService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class OfferAction implements RequestHandlerInterface
{
    public function __construct(private OfferService $offers) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $payload = (array)$request->getParsedBody();
        $page = max(1, (int)($request->getQueryParams()['page'] ?? 1));
        if (str_starts_with($path, '/v1/offers/')) {
            return new JsonDataResponse($this->offers->read((string)Route::getArgument($request, 'id')));
        }
        $user = RequestIdentity::get($request)->id;
        if ($request->getMethod() === 'PATCH') {
            $this->offers->disable($user, (string)Route::getArgument($request, 'id'));
            $result = ['disabled' => true];
        } else {
            $result = $request->getMethod() === 'GET' ? $this->offers->listing($user, $page) : $this->offers->create($user, $payload);
        }
        return new JsonDataResponse($result);
    }
}
