<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Withdrawal;

use App\Modules\Command\Withdrawal\Create\Command;
use App\Modules\Command\Withdrawal\Create\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class CreateAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private EntityManagerInterface $em,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);

        $withdrawalId = 'wd-' . time();
        $command = new Command(
            withdrawalId: $withdrawalId,
            userId: (int)($body['userId'] ?? 0),
            amount: (int)($body['amount'] ?? 0),
        );

        try {
            $withdrawal = $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode([
                'id' => $withdrawal->getId(),
                'userId' => $withdrawal->getUserId(),
                'amount' => $withdrawal->getAmount(),
                'status' => $withdrawal->getStatus(),
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
