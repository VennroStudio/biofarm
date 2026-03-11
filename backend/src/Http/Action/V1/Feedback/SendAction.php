<?php

declare(strict_types=1);

namespace App\Http\Action\V1\Feedback;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class SendAction implements RequestHandlerInterface
{
    private const RECIPIENT_EMAIL = 'Miss.sperkach@mail.ru';

    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true) ?? [];
        $name = trim((string)($body['name'] ?? ''));
        $phone = trim((string)($body['phone'] ?? ''));
        $email = trim((string)($body['email'] ?? ''));
        $message = trim((string)($body['message'] ?? ''));

        if ($name === '' || $email === '' || $message === '') {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Заполните имя, email и сообщение',
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Некорректный email',
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $text = "Имя: {$name}\n";
        $text .= "Email: {$email}\n";
        if ($phone !== '') {
            $text .= "Телефон: {$phone}\n";
        }
        $text .= "\nСообщение:\n{$message}";

        $subject = 'Заявка с сайта Biofarm: ' . mb_substr($message, 0, 50);
        if (mb_strlen($message) > 50) {
            $subject .= '…';
        }

        $emailMessage = (new Email())
            ->from($this->fromEmail)
            ->to(self::RECIPIENT_EMAIL)
            ->replyTo($email)
            ->subject($subject)
            ->text($text);

        try {
            $this->mailer->send($emailMessage);
        } catch (\Throwable $e) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'Не удалось отправить сообщение. Попробуйте позже.',
            ], JSON_UNESCAPED_UNICODE));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Сообщение отправлено',
        ], JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
