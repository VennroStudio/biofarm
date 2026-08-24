<?php

declare(strict_types=1);

namespace App\Http\Action\Admin\Faq;

use App\Components\Exception\DomainExceptionModule;
use App\Components\Http\Response\JsonDataResponse;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

final readonly class SaveFaqItemAction implements RequestHandlerInterface
{
    private const array SCOPES = ['global', 'home', 'catalog', 'category', 'attribute', 'product', 'blog', 'post', 'page', 'certificates'];

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
        $question = trim((string)($payload['question'] ?? ''));
        $answer = trim((string)($payload['answer'] ?? ''));

        if ($question === '') {
            throw new DomainExceptionModule('faq', 'error.faq_question_required', 1, status: 422);
        }

        if ($answer === '') {
            throw new DomainExceptionModule('faq', 'error.faq_answer_required', 2, status: 422);
        }

        $scope = (string)($payload['page_scope'] ?? 'global');
        if (!\in_array($scope, self::SCOPES, true)) {
            throw new DomainExceptionModule('faq', 'error.faq_scope_invalid', 3, status: 422);
        }

        $data = [
            'question'   => mb_substr($question, 0, 500),
            'answer'     => $answer,
            'page_scope' => $scope,
            'page_id'    => $this->nullablePageId($payload['page_id'] ?? null),
            'is_active'  => !isset($payload['is_active']) || (bool)$payload['is_active'] ? 1 : 0,
            'sort_order' => max(0, (int)($payload['sort_order'] ?? 0)),
            'updated_at' => gmdate('Y-m-d H:i:s'),
        ];

        $isCreate = $id === null;
        if ($id !== null) {
            if (!$this->exists($id)) {
                throw new DomainExceptionModule('faq', 'error.faq_not_found', 4, status: 404);
            }

            $this->connection->update('faq_items', $data, ['id' => $id]);
        } else {
            $data['created_at'] = gmdate('Y-m-d H:i:s');
            $this->connection->insert('faq_items', $data);
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

    private function nullablePageId(mixed $value): ?string
    {
        $value = trim((string)$value);

        return $value !== '' ? mb_substr($value, 0, 100) : null;
    }

    /**
     * @throws Exception
     */
    private function exists(int $id): bool
    {
        return (bool)$this->connection->fetchOne(
            'SELECT 1 FROM faq_items WHERE id = :id LIMIT 1',
            ['id' => $id],
        );
    }
}
