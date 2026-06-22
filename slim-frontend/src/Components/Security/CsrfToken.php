<?php

declare(strict_types=1);

namespace App\Components\Security;

use App\Components\Http\Form\FormValidationException;

final readonly class CsrfToken
{
    public function __construct(
        private string $secret,
    ) {}

    public function generate(string $action): string
    {
        return $this->base64UrlEncode(hash_hmac('sha256', $action, $this->secret, true));
    }

    public function validate(string $action, string $token): void
    {
        if (!hash_equals($this->generate($action), $token)) {
            throw FormValidationException::invalidCsrfToken();
        }
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
