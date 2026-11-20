<?php

declare(strict_types=1);

namespace App\Modules\Feedback\Service;

use App\Components\Integration\IntegrationErrorLogger;
use App\Components\Setting\SiteSettings;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Throwable;

final readonly class FeedbackEmailNotifier
{
    public function __construct(
        private SiteSettings $settings,
        private MailerInterface $mailer,
        private IntegrationErrorLogger $errorLogger,
    ) {}

    /**
     * @param array{name: string, phone: string|null, email: string|null, message: string, source_page: string|null} $feedback
     */
    public function submitted(int $feedbackId, array $feedback): bool
    {
        $recipient = trim((string)$this->settings->get('site_email', ''));
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        try {
            $email = new Email()
                ->to($recipient)
                ->subject('Заявка с сайта БИОФАРМ')
                ->text($this->body($feedback));

            if ($feedback['email'] !== null) {
                $email->replyTo($feedback['email']);
            }

            $this->mailer->send($email);

            return true;
        } catch (Throwable $exception) {
            $this->errorLogger->log(
                service: 'email',
                scenario: 'feedback_form',
                operation: 'send',
                message: $exception->getMessage(),
                localEntityType: 'feedback_request',
                localEntityId: (string)$feedbackId,
            );

            return false;
        }
    }

    /**
     * @param array{name: string, phone: string|null, email: string|null, message: string, source_page: string|null} $feedback
     */
    private function body(array $feedback): string
    {
        return implode("\n", [
            'Имя: ' . $feedback['name'],
            'Телефон: ' . ($feedback['phone'] ?? ''),
            'Email: ' . ($feedback['email'] ?? ''),
            'Страница: ' . ($feedback['source_page'] ?? ''),
            '',
            $feedback['message'],
        ]);
    }
}
