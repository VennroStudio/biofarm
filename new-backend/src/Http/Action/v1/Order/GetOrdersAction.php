<?php

declare(strict_types=1);

namespace App\Http\Action\v1\Order;

use App\Components\Http\Response\JsonDataItemsResponse;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Serializer\Denormalizer;
use App\Components\Validator\Validator;
use App\Http\Unifier\Order\OrderUnifier;
use App\Modules\Order\Query\Order\FindAll\OrderFindAllFetcher;
use App\Modules\Order\Query\Order\FindAll\OrderFindAllQuery;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use Doctrine\DBAL\Exception;
use OpenApi\Attributes as OA;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[OA\Get(path: '/orders', summary: 'Список заказов', security: [['bearerAuth' => []]], tags: ['Orders'])]
final readonly class GetOrdersAction implements RequestHandlerInterface
{
    public function __construct(
        private OrderFindAllFetcher $fetcher,
        private OrderUnifier $unifier,
        private Denormalizer $denormalizer,
        private Validator $validator,
    ) {}

    /**
     * @throws Exception
     * @throws ExceptionInterface
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->denormalizer->denormalize($request->getQueryParams(), OrderFindAllQuery::class);
        $identity = RequestIdentity::get($request);

        if ($identity->role === UserRole::USER) {
            $query->userId = $identity->id;
            $query->referredBy = null;
        }

        $this->validator->validate($query);
        $result = $this->fetcher->fetch($query);

        return new JsonDataItemsResponse(
            count: $result->count,
            items: $this->unifier->unify(null, $result->items),
        );
    }
}
