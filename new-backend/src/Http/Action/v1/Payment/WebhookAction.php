<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Payment;

use App\Components\Http\Response\JsonDataResponse;
use App\Modules\Payment\Service\PaymentService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class WebhookAction implements RequestHandlerInterface
{
    public function __construct(private PaymentService $payments) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->payments->webhook((array)$request->getParsedBody());
        return new JsonDataResponse(['received' => true]);
    }
}
