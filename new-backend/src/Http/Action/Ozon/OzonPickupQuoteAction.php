<?php

declare(strict_types=1);

namespace App\Http\Action\Ozon;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonResponse;
use App\Components\Privacy\ConsentRecorder;
use App\Modules\Ozon\OzonException;
use App\Modules\Ozon\OzonPickupCheckout;
use App\Modules\User\Service\Phone\PhoneAccounts;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class OzonPickupQuoteAction implements RequestHandlerInterface
{
    public function __construct(
        private OzonPickupCheckout $checkout,
        private PhoneAccounts $phones,
        private ConsentRecorder $consents,
        private Connection $db,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->checkout->ready()) {
            return new JsonResponse(['error' => 'Доставка Ozon временно недоступна.'], 404);
        }

        $input = (array) $request->getParsedBody();
        try {
            $method = $input['method'] ?? null;
            $items = $input['items'] ?? null;
            $phone = $input['phone'] ?? null;
            if (!is_array($items) || !is_string($phone)) {
                throw new OzonException('Проверьте параметры доставки.');
            }
            if ($method !== OzonPickupCheckout::LIVE_PICKUP) {
                throw new OzonException('Выберите пункт выдачи Ozon.');
            }
            $identity = RequestIdentity::find($request);
            $phone = $this->phones->checkoutPhone($identity, $phone);
            if ($this->consents->requirePersonalData($input, $identity->id)) {
                $this->db->transactional(function () use ($request, $identity, $input): void {
                    if ($this->consents->requirePersonalData($input, $identity->id, lock: true)) {
                        $this->consents->record($request, 'user', (string)$identity->id, 'personal_data', true);
                    }
                });
            }
            $quote = $this->checkout->prepare($items, $phone, $identity->id, $input['pickupPointId'] ?? null);
            return new JsonDataResponse([
                'fingerprint' => $this->checkout->fingerprint($quote),
                'dates' => $quote['dates'],
                'deliveryCost' => $quote['deliveryCostMinor'] / 100,
            ]);
        } catch (OzonException $exception) {
            return new JsonResponse(['error' => $exception->getCustomerMessage()], 422);
        }
    }
}
