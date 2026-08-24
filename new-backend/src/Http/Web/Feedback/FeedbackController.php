<?php

declare(strict_types=1);

namespace App\Http\Web\Feedback;

use App\Components\Integration\Bitrix24\Bitrix24FeedbackSyncer;
use App\Modules\Feedback\Service\FeedbackEmailNotifier;
use Doctrine\DBAL\Connection;
use Override;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class FeedbackController implements RequestHandlerInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private Connection $connection,
        private FeedbackEmailNotifier $emailNotifier,
        private Bitrix24FeedbackSyncer $bitrix24FeedbackSyncer,
        private LoggerInterface $logger,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = (array)$request->getParsedBody();
        if (trim((string)($payload['website'] ?? '')) !== '') {
            return $this->redirect();
        }

        $feedback = [
            'name'        => mb_substr(trim((string)($payload['name'] ?? '')), 0, 255),
            'phone'       => $this->nullableString($payload['phone'] ?? null, 50),
            'email'       => $this->email($payload['email'] ?? null),
            'message'     => trim((string)($payload['message'] ?? '')),
            'source_page' => $this->sourcePage($request),
        ];

        if ($feedback['name'] === '' || $feedback['message'] === '') {
            return $this->redirect();
        }

        try {
            $this->connection->insert('feedback_requests', [
                'name'        => $feedback['name'],
                'phone'       => $feedback['phone'],
                'email'       => $feedback['email'],
                'message'     => $feedback['message'],
                'source_page' => $feedback['source_page'],
                'ip'          => $this->nullableString($request->getServerParams()['REMOTE_ADDR'] ?? null, 45),
                'user_agent'  => $this->nullableString($request->getHeaderLine('User-Agent'), 500),
                'created_at'  => gmdate('Y-m-d H:i:s'),
            ]);

            $feedbackId = (int)$this->connection->lastInsertId();
            if ($this->emailNotifier->submitted($feedbackId, $feedback)) {
                $this->connection->update('feedback_requests', ['email_sent_at' => gmdate('Y-m-d H:i:s')], ['id' => $feedbackId]);
            }
            $this->bitrix24FeedbackSyncer->submitted($feedbackId, $feedback);
        } catch (Throwable $exception) {
            $this->logger->warning('Feedback request was not processed.', [
                'error' => $exception->getMessage(),
            ]);
        }

        return $this->redirect();
    }

    private function redirect(): ResponseInterface
    {
        return $this->responseFactory->createResponse(303)
            ->withHeader('Location', '/#partner');
    }

    private function email(mixed $value): ?string
    {
        $email = $this->nullableString($value, 255);

        return $email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }

    private function nullableString(mixed $value, int $limit): ?string
    {
        $value = mb_substr(trim((string)$value), 0, $limit);

        return $value !== '' ? $value : null;
    }

    private function sourcePage(ServerRequestInterface $request): ?string
    {
        $referer = $request->getHeaderLine('Referer');
        if ($referer === '') {
            return null;
        }

        $path = parse_url($referer, PHP_URL_PATH);

        return \is_string($path) && $path !== '' ? mb_substr($path, 0, 500) : null;
    }
}
