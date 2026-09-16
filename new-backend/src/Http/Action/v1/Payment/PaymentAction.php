<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Payment;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class PaymentAction implements RequestHandlerInterface
{
    public function __construct(private PaymentService $payments) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = (string)Route::getArgument($request, 'orderId');
        $identity = RequestIdentity::find($request);
        $payload = (array)$request->getParsedBody();
        $token = $request->getHeaderLine('X-Order-Token') ?: (string)($payload['token'] ?? '');
        $this->payments->authorize($id, $identity?->id, $token, $identity?->role === UserRole::ADMIN);
        return new JsonDataResponse($request->getMethod() === 'POST' ? $this->payments->start($id) : $this->payments->status($id));
    }
}
