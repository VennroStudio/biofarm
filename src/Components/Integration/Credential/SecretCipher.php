<?php

declare(strict_types=1);

namespace App\Components\Integration\Credential;

use RuntimeException;

final readonly class SecretCipher
{
    private string $key;

    public function __construct(string $appSecret)
    {
        if ($appSecret === '') {
            throw new RuntimeException('Application secret is required to encrypt integration credentials.');
        }

        $this->key = hash('sha256', $appSecret, true);
    }

    public function encrypt(string $value): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($value, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new RuntimeException('Integration credential encryption failed.');
        }

        return base64_encode(json_encode([
            'iv'    => base64_encode($iv),
            'tag'   => base64_encode($tag),
            'value' => base64_encode($ciphertext),
        ], JSON_THROW_ON_ERROR));
    }

    public function decrypt(string $payload): string
    {
        $decoded = json_decode(base64_decode($payload, true) ?: '', true, flags: JSON_THROW_ON_ERROR);
        if (!\is_array($decoded)) {
            throw new RuntimeException('Integration credential payload is invalid.');
        }

        $iv = base64_decode((string)($decoded['iv'] ?? ''), true);
        $tag = base64_decode((string)($decoded['tag'] ?? ''), true);
        $ciphertext = base64_decode((string)($decoded['value'] ?? ''), true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            throw new RuntimeException('Integration credential payload is corrupted.');
        }

        $value = openssl_decrypt($ciphertext, 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($value === false) {
            throw new RuntimeException('Integration credential decryption failed.');
        }

        return $value;
    }
}
