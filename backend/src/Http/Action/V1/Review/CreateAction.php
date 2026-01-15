<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Review;

use App\Modules\Command\Review\Create\Command;
use App\Modules\Command\Review\Create\Handler;
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

        $reviewId = 'rev-' . time();
        $command = new Command(
            reviewId: $reviewId,
            productId: (int)($body['productId'] ?? 0),
            userName: $body['userName'] ?? '',
            rating: (int)($body['rating'] ?? 5),
            text: $body['text'] ?? '',
            source: $body['source'] ?? 'site',
            userId: $body['userId'] ?? null,
            images: $body['images'] ?? null,
            isApproved: false,
        );

        try {
            $review = $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode([
                'id' => $review->getId(),
                'productId' => $review->getProductId(),
                'userName' => $review->getUserName(),
                'rating' => $review->getRating(),
                'text' => $review->getText(),
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
