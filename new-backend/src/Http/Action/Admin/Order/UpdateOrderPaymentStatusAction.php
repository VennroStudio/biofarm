<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Order;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Response\JsonDataSuccessResponse;
use App\Components\Router\Route;
use App\Modules\Order\Entity\Order\OrderRepository;
use App\Modules\Order\Service\OrderBonusApplier;
use App\Modules\Order\Service\OrderEmailNotifier;
use App\Modules\Order\Service\OrderStatusGuard;
use App\Modules\Program\Service\ProgramService;
use DateMalformedStringException;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateOrderPaymentStatusAction implements RequestHandlerInterface
{
    public function __construct(
        private ProgramService $program,
        private OrderRepository $repository,
        private OrderBonusApplier $bonusApplier,
        private OrderEmailNotifier $emailNotifier,
        private OrderStatusGuard $statusGuard,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $order = $this->program->atomic(function () use ($request) {
            $order = $this->repository->getById(Route::getArgument($request, 'id'));
            $payload = (array)$request->getParsedBody();
            $paymentStatus = $this->statusGuard->paymentStatus((string)($payload['paymentStatus'] ?? $payload['payment_status'] ?? $order->paymentStatus));
            $this->statusGuard->adminTransition($order, $order->status, $paymentStatus);
            $order->updatePaymentStatus($paymentStatus);
            $this->bonusApplier->apply($order);
            $this->cacher->deleteTag('orders');
            $this->cacher->delete('order_by_id_' . $order->id);
            $this->flusher->flush();
            return $order;
        });
        $this->emailNotifier->updated($order);

        return new JsonDataSuccessResponse(1, 200);
    }
}
