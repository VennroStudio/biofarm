<?php

declare(strict_types=1);

namespace App\Http\Action\v1\PartnerOffer;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\PartnerOffer\Service\OfferService;
use App\Modules\PartnerOffer\Service\PartnerPromoService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class OfferAction implements RequestHandlerInterface
{
    public function __construct(private OfferService $offers, private PartnerPromoService $promos) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $payload = (array)$request->getParsedBody();
        $page = max(1, (int)($request->getQueryParams()['page'] ?? 1));
        if (str_starts_with($path, '/v1/offers/')) {
            return new JsonDataResponse($this->offers->read((string)Route::getArgument($request, 'id')));
        }
        $user = RequestIdentity::get($request)->id;
        if (str_contains($path, '/promo-requests')) {
            if (str_starts_with($path, '/admin/')) {
                $result = $request->getMethod() === 'GET' ? $this->promos->listing(null, $page) : $this->promos->decide((string)Route::getArgument($request, 'id'), $user, $payload);
            } else {
                $result = $request->getMethod() === 'GET' ? $this->promos->listing($user, $page) : $this->promos->request($user, $payload);
            }
        } elseif ($request->getMethod() === 'PATCH') {
            $this->offers->disable($user, (string)Route::getArgument($request, 'id'));
            $result = ['disabled' => true];
        } else {
            $result = $request->getMethod() === 'GET' ? $this->offers->listing($user, $page) : $this->offers->create($user, $payload);
        }
        return new JsonDataResponse($result);
    }
}
