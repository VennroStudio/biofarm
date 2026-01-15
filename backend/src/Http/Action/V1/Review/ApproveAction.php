<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Review;

use App\Modules\Command\Review\Approve\Command;
use App\Modules\Command\Review\Approve\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class ApproveAction implements RequestHandlerInterface
{
    public function __construct(
        private Handler $handler,
        private EntityManagerInterface $em,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Try multiple ways to get the ID from route
        $route = $request->getAttribute('route');
        $reviewId = '';
        
        if ($route) {
            $reviewId = $route->getArgument('id') ?? '';
        }
        
        // If still empty, try to get from URI (fallback for Slim routing issues)
        if ($reviewId === '') {
            $path = $request->getUri()->getPath();
            if (preg_match('/\/reviews\/([^\/]+)/', $path, $matches)) {
                $reviewId = $matches[1];
            }
        }
        
        if ($reviewId === '') {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => 'Review ID is required'], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $command = new Command(reviewId: $reviewId);

        try {
            $review = $this->handler->handle($command);
            $this->em->flush();

            $response = new Response();
            $response->getBody()->write(json_encode([
                'id' => $review->getId(),
                'isApproved' => $review->isApproved(),
            ], JSON_UNESCAPED_UNICODE));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
