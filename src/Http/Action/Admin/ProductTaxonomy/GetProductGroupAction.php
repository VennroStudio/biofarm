<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\ProductTaxonomy;

use App\Components\Http\Response\JsonDataResponse;
use App\Components\Router\Route;
use App\Modules\Product\Service\ProductGroupMembers;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class GetProductGroupAction implements RequestHandlerInterface
{
    public function __construct(private ProductGroupMembers $members, private Connection $db) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = Route::getArgumentToInt($request, 'id');
        return new JsonDataResponse($this->db->transactional(fn () => $this->members->details($id)));
    }
}
