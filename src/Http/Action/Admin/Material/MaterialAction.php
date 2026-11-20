<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Material;

use App\Components\Http\Response\JsonDataResponse;
use App\Modules\Content\Service\MaterialLibrary;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class MaterialAction implements RequestHandlerInterface
{
    public function __construct(private MaterialLibrary $library) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $args = RouteContext::fromRequest($request)->getRoute()?->getArguments() ?? [];
        $kind = $args['kind'] ?? '';
        $id = isset($args['id']) ? (int)$args['id'] : null;
        $payload = (array)$request->getParsedBody();
        $method = $request->getMethod();
        $resource = $args['resource'] ?? 'materials';
        if ($resource === 'targets') {
            $data = $this->library->targets($request->getQueryParams());
        } elseif ($resource === 'selections') {
            $data = $this->library->selections($args['type'], $args['id']);
        } elseif ($resource === 'categories') {
            $data = match ($method) {
                'GET'    => $this->library->categories($kind),
                'DELETE' => $this->deleteCategory($kind, (int)$id),
                default  => ['id' => $this->library->saveCategory($kind, $id, $payload)],
            };
        } elseif ($resource === 'bulk') {
            $this->library->bulkAttach($kind, (array)($payload['ids'] ?? []), (array)($payload['placements'] ?? []));
            $data = ['success' => true];
        } else {
            $data = match ($method) {
                'GET'    => $id === null ? $this->library->listing($kind, $request->getQueryParams()) : $this->library->get($kind, $id),
                'DELETE' => $this->delete($kind, (int)$id),
                default  => ['id' => $this->library->save($kind, $id, $payload)],
            };
        }
        return new JsonDataResponse($data, $method === 'POST' && $resource !== 'bulk' ? 201 : 200);
    }

    private function delete(string $kind, int $id): array
    {
        $this->library->delete($kind, $id);
        return ['success' => true];
    }

    private function deleteCategory(string $kind, int $id): array
    {
        $this->library->deleteCategory($kind, $id);
        return ['success' => true];
    }
}
