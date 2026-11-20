<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Certificate;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class SaveCertificateAction implements RequestHandlerInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    /**
     * @throws Exception
     */
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->routeId($request);
        $payload = (array)$request->getParsedBody();
        $title = trim((string)($payload['title'] ?? ''));
        $filePath = trim((string)($payload['file_path'] ?? ''));

        if ($title === '') {
            throw new DomainExceptionModule('certificate', 'error.certificate_title_required', 1, status: 422);
        }

        if ($filePath === '') {
            throw new DomainExceptionModule('certificate', 'error.certificate_file_required', 2, status: 422);
        }

        if (!str_starts_with($filePath, '/uploads/')) {
            throw new DomainExceptionModule('certificate', 'error.certificate_file_invalid', 3, status: 422);
        }

        $productId = $this->nullablePositiveInt($payload['product_id'] ?? null);
        if ($productId !== null && !$this->productExists($productId)) {
            throw new DomainExceptionModule('certificate', 'error.certificate_product_not_found', 4, status: 422);
        }

        $data = [
            'title'         => $title,
            'file_path'     => $filePath,
            'document_type' => $this->documentType($payload['document_type'] ?? '', $filePath),
            'product_id'    => $productId,
            'description'   => $this->nullableText($payload['description'] ?? null, 500),
            'is_active'     => !isset($payload['is_active']) || (bool)$payload['is_active'] ? 1 : 0,
            'sort_order'    => max(0, (int)($payload['sort_order'] ?? 0)),
            'updated_at'    => gmdate('Y-m-d H:i:s'),
        ];

        $isCreate = $id === null;
        if ($id !== null) {
            if (!$this->certificateExists($id)) {
                throw new DomainExceptionModule('certificate', 'error.certificate_not_found', 5, status: 404);
            }

            $this->connection->update('certificates', $data, ['id' => $id]);
        } else {
            $data['created_at'] = gmdate('Y-m-d H:i:s');
            $this->connection->insert('certificates', $data);
            $id = (int)$this->connection->lastInsertId();
        }

        return new JsonDataResponse(['id' => $id], $isCreate ? 201 : 200);
    }

    private function routeId(ServerRequestInterface $request): ?int
    {
        $argument = RouteContext::fromRequest($request)
            ->getRoute()
            ?->getArgument('id');

        return $argument !== null ? (int)$argument : null;
    }

    private function documentType(mixed $value, string $filePath): string
    {
        $type = strtolower(trim((string)$value));
        if (\in_array($type, ['pdf', 'image'], true)) {
            return $type;
        }

        return str_ends_with(strtolower($filePath), '.pdf') ? 'pdf' : 'image';
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        $id = (int)$value;

        return $id > 0 ? $id : null;
    }

    private function nullableText(mixed $value, int $limit): ?string
    {
        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }

        return mb_substr($text, 0, $limit);
    }

    /**
     * @throws Exception
     */
    private function productExists(int $id): bool
    {
        return (bool)$this->connection->fetchOne(
            'SELECT 1 FROM products WHERE id = :id AND deleted_at IS NULL LIMIT 1',
            ['id' => $id],
        );
    }

    /**
     * @throws Exception
     */
    private function certificateExists(int $id): bool
    {
        return (bool)$this->connection->fetchOne(
            'SELECT 1 FROM certificates WHERE id = :id LIMIT 1',
            ['id' => $id],
        );
    }
}
