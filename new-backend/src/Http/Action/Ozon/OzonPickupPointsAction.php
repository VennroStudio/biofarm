<?php

declare(strict_types=1);

namespace App\Http\Action\Ozon;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Http\Response\JsonResponse;
use App\Modules\Ozon\OzonException;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\Ozon\OzonPickupDirectory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class OzonPickupPointsAction implements RequestHandlerInterface
{
    public function __construct(private OzonPickupCheckout $checkout, private OzonPickupDirectory $directory) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->checkout->ready()) {
            return new JsonResponse(['error' => 'Доставка Ozon временно недоступна.'], 404);
        }

        try {
            $query = $request->getQueryParams()['q'] ?? '';
            return new JsonDataResponse($this->directory->search(is_string($query) ? $query : ''));
        } catch (OzonException) {
            return new JsonResponse(['error' => 'Не удалось загрузить пункты выдачи Ozon. Попробуйте ещё раз.'], 502);
        }
    }
}
