<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Withdrawal;

use App\Components\Flusher\FlusherInterface;
use App\Components\Http\Response\JsonDataResponse;
use App\Components\Id\ReadableIdGenerator;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequest;
use App\Modules\Withdrawal\Entity\WithdrawalRequest\WithdrawalRequestRepository;
use DateMalformedStringException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Random\RandomException;

final readonly class CreateWithdrawalAction implements RequestHandlerInterface
{
    public function __construct(
        private ReadableIdGenerator $idGenerator,
        private WithdrawalRequestRepository $repository,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = (array)$request->getParsedBody();
        $withdrawal = WithdrawalRequest::create(
            id: $this->idGenerator->generate('wd'),
            userId: (int)($payload['userId'] ?? $payload['user_id'] ?? 0),
            amount: (int)($payload['amount'] ?? 0),
        );

        $this->repository->add($withdrawal);
        $this->flusher->flush();

        return new JsonDataResponse([
            'id'         => $withdrawal->id,
            'user_id'    => $withdrawal->userId,
            'amount'     => $withdrawal->amount,
            'status'     => $withdrawal->status->value,
            'created_at' => $withdrawal->createdAt->format(DATE_ATOM),
        ], 201);
    }
}
