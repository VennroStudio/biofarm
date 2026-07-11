<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Order;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Modules\Order\Entity\Order\OrderRepository;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateOrderPaymentStatusAction implements RequestHandlerInterface
{
    public function __construct(
        private OrderRepository $repository,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $order = $this->repository->getById(Route::getArgument($request, 'id'));
        $payload = (array)$request->getParsedBody();
        $order->updatePaymentStatus((string)($payload['paymentStatus'] ?? $payload['payment_status'] ?? $order->paymentStatus));

        $this->cacher->deleteTag('orders');
        $this->cacher->delete('order_by_id_' . $order->id);
        $this->flusher->flush();

        return new JsonDataSuccessResponse(1, 200);
    }
}
