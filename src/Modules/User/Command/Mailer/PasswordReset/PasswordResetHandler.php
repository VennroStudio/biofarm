<?php

declare(strict_types=1);

namespace App\Modules\User\Command\Mailer\PasswordReset;

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

final readonly class PasswordResetHandler
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
    public function handle(PasswordResetCommand $command): void
    {
        $email = $this->buildEmail($command);
        $this->mailer->send($email);
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function buildEmail(PasswordResetCommand $command): Email
    {
        return new Email()
            ->to($command->email)
            ->subject($this->buildSubject())
            ->html($this->buildHtml($command));
    }

    private function buildSubject(): string
    {
        return $this->translator->trans('mail.password_reset.subject', [], 'passwordReset');
    }

    /**
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function buildHtml(PasswordResetCommand $command): string
    {
        return $this->twig->render('email/password-reset.html.twig', [
            'resetUrl' => $this->buildResetUrl($command->token),
            'locale'   => $command->locale,
        ]);
    }

    private function buildResetUrl(string $token): string
    {
        return $this->frontendUrl->generate('password-reset-confirm', [
            'token' => $token,
        ], FrontendUrlType::AUTH);
    }
}
