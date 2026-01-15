<?php

declare(strict_types=1);

namespace App\Http\Action\V1\BlogPost;

use App\Modules\Query\BlogPosts\GetAll\Fetcher;
use App\Modules\Query\BlogPosts\GetAll\Query;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final readonly class GetAllAction implements RequestHandlerInterface
{
    public function __construct(
        private Fetcher $fetcher,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = new Query(onlyPublished: true);
        $posts = $this->fetcher->fetch($query);

        $response = new Response();
        $serialized = array_map([\App\Http\Serializer::class, 'blogPost'], $posts);
        $response->getBody()->write(json_encode($serialized, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
