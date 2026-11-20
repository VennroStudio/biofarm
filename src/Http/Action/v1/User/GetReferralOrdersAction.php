<?php

declare(strict_types=1);

namespace App\Http\Action\v1\User;

use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Modules\Program\Service\ProgramService;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetReferralOrdersAction implements RequestHandlerInterface
{
    public function __construct(private ProgramService $program) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        return new JsonDataResponse($this->program->listing('sales', RequestIdentity::get($request)->id, (int)($query['page'] ?? 1), (int)($query['limit'] ?? 25)));
    }
}
