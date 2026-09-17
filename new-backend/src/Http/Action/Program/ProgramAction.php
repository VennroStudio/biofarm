<?php

declare(strict_types=1);

namespace App\Http\Action\Program;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Middleware\Identity\RequestIdentity;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\Payment\Service\PaymentService;
use App\Modules\Program\Service\ProgramMath;
use App\Modules\Program\Service\ProgramService;
use DomainException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ProgramAction implements RequestHandlerInterface
{
    public function __construct(private ProgramService $program, private PaymentService $payments) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestIdentity::get($request)->id;
        $path = $request->getUri()->getPath();
        $admin = str_starts_with($path, '/admin/');
        $base = $admin ? '/admin/api/program' : '/v1/program';
        $part = trim(substr($path, \strlen($base)), '/');
        $body = (array)$request->getParsedBody();
        $query = $request->getQueryParams();
        $method = $request->getMethod();
        try {
            $result = match (true) {
                !$admin && $method === 'GET' && $part === 'team-invitation'                                                   => $this->program->teamInvite($actor),
                !$admin && $method === 'POST' && $part === 'join'                                                             => $this->program->joinTeam($actor, (string)($body['code'] ?? ''), ($body['consent'] ?? false) === true),
                $method === 'GET' && $part === ''                                                                             => $admin ? ['rates' => $this->program->settings(), 'withdrawals' => $this->program->listing('withdrawals', null, 1, 25), 'rateUnit' => 'basis_points', 'moneyUnit' => 'minor'] : $this->program->dashboard($actor),
                $method === 'GET' && $part === 'settings' && $admin                                                           => $this->program->settings(),
                $method === 'PATCH' && $part === 'settings' && $admin                                                         => $this->program->updateSettings($body, $actor),
                $method === 'GET' && \in_array($part, ['team', 'referrals', 'ledger', 'sales', 'withdrawals', 'audit'], true) => $this->program->listing($part, $admin ? null : $actor, (int)($query['page'] ?? 1), (int)($query['limit'] ?? 25), (string)($query['sort'] ?? 'depth'), (string)($query['direction'] ?? 'asc'), isset($query['wallet']) ? (string)$query['wallet'] : null, isset($query['dateFrom']) ? (string)$query['dateFrom'] : null, isset($query['dateTo']) ? (string)$query['dateTo'] : null),
                $method === 'POST' && $part === 'withdrawals'                                                                 => $this->program->requestWithdrawal($actor, $body['amount'] ?? '0', (array)($body['details'] ?? [])),
                $method === 'PATCH' && str_starts_with($part, 'withdrawals/') && $admin                                       => $this->program->updateWithdrawal(Route::getArgument($request, 'id'), (string)($body['status'] ?? ''), isset($body['reference']) ? (string)$body['reference'] : null, isset($body['reason']) ? (string)$body['reason'] : null, $actor),
                $method === 'POST' && $part === 'simulate' && $admin                                                          => $this->simulate($body),
                $method === 'POST' && str_starts_with($part, 'orders/') && $admin                                             => $this->deliver(Route::getArgument($request, 'id')),
                $method === 'POST' && $part === 'adjustments' && $admin                                                       => $this->adjust($body, $actor),
                default                                                                                                       => throw new DomainException('Unsupported program operation'),
            };
            return new JsonDataResponse($result);
        } catch (DomainException $e) {
            throw new DomainExceptionModule('program', $e->getMessage(), 1, status: 422);
        }
    }

    private function adjust(array $body, int $actor): array
    {
        $this->program->adjust((int)($body['userId'] ?? 0), (string)($body['wallet'] ?? ''), (int)($body['amountMinor'] ?? 0), (string)($body['reason'] ?? ''), $actor);
        return ['adjusted' => true];
    }

    private function deliver(string $id): array
    {
        $this->program->atomic(function () use ($id): void {
            $this->program->deliverOrder($id);
            $this->payments->queueSettlementReceipt($id);
        });
        return ['delivered' => true];
    }

    private function simulate(array $body): array
    {
        $r = ProgramMath::rules((array)($body['rates'] ?? []), $this->program->settings());
        return ProgramMath::simulate($body, $r);
    }
}
