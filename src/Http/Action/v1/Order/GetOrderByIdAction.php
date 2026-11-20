<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Order;

use App\Components\Exception\AccessDeniedException;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Http\Unifier\Order\OrderUnifier;
use App\Modules\Order\Query\Order\GetById\OrderGetByIdFetcher;
use App\Modules\Order\Query\Order\GetById\OrderGetByIdQuery;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[OA\Get(path: '/orders/{id}', summary: 'Заказ по id', security: [['bearerAuth' => []]], tags: ['Orders'])]
final readonly class GetOrderByIdAction implements RequestHandlerInterface
{
    public function __construct(
        private OrderGetByIdFetcher $fetcher,
        private OrderUnifier $unifier,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $identity = RequestIdentity::get($request);
        $order = $this->fetcher->fetch(new OrderGetByIdQuery(Route::getArgument($request, 'id')));

        if ($identity->role === UserRole::USER && $order->userId !== $identity->id) {
            throw new AccessDeniedException();
        }

        return new JsonDataResponse($this->unifier->unifyOne(null, $order));
    }
}
