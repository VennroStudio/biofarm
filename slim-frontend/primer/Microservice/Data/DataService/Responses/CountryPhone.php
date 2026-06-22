<?php

declare(strict_types=1);

namespace App\Components\Microservice\Data\DataService\Responses;

final readonly class CountryPhone
{
    public function __construct(
        public string $code,
        public int $length,
        public string $mask,
        public bool $isMain,
    ) {}

    /**
     * @param array<int, array{code: string, length: int, mask: string, isMain: bool}> $phones
     * @return CountryPhone[]
     */
    public static function fromArrayList(array $phones): array
    {
        $result = [];
        foreach ($phones as $phone) {
            $result[] = new self(
                code: $phone['code'] ?? '',
                length: $phone['length'] ?? 0,
                mask: $phone['mask'] ?? '',
                isMain: $phone['isMain'] ?? false,
            );
        }
        return $result;
    }
}
