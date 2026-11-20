<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Mailer\EmailVerification;

use App\Components\Frontend\FrontendUrlGenerator;
use App\Components\Frontend\FrontendUrlType;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class EmailVerificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private TranslatorInterface $translator,
        private FrontendUrlGenerator $frontendUrl,
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function handle(EmailVerificationCommand $command): void
    {
        $email = $this->buildEmail($command);
        $this->mailer->send($email);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function buildEmail(EmailVerificationCommand $command): Email
    {
        return new Email()
            ->to($command->email)
            ->subject($this->buildSubject())
            ->html($this->buildHtml($command));
    }

    private function buildSubject(): string
    {
        return $this->translator->trans('mail.email_verification.subject', [], 'emailVerification');
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function buildHtml(EmailVerificationCommand $command): string
    {
        return $this->twig->render('email/email-verification.html.twig', [
            'firstName'       => $command->firstName,
            'confirmationUrl' => $this->buildConfirmationUrl($command->token),
            'locale'          => $command->locale,
        ]);
    }

    private function buildConfirmationUrl(string $token): string
    {
        return $this->frontendUrl->generate('email-verification', [
            'token' => $token,
        ], FrontendUrlType::AUTH);
    }
}
