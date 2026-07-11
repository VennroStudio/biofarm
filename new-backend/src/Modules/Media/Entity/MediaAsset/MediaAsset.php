<?php

declare(strict_types=1);

namespace App\Modules\Media\Entity\MediaAsset;

use App\Components\Clock\UtcClock;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'media_assets')]
class MediaAsset
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private(set) string $path;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private(set) string $url;

    #[ORM\Column(name: 'mime_type', type: Types::STRING, length: 100)]
    private(set) string $mimeType;

    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $size;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private(set) ?int $width;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private(set) ?int $height;

    #[ORM\Column(name: 'original_name', type: Types::STRING, length: 255, nullable: true)]
    private(set) ?string $originalName;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) DateTimeImmutable $createdAt;

    /**
     * @throws DateMalformedStringException
     */
    private function __construct(
        string $path,
        string $url,
        string $mimeType,
        int $size,
        ?int $width,
        ?int $height,
        ?string $originalName,
    ) {
        $this->path = $path;
        $this->url = $url;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->width = $width;
        $this->height = $height;
        $this->originalName = $originalName;
        $this->createdAt = UtcClock::now();
    }

    /**
     * @throws DateMalformedStringException
     */
    public static function create(
        string $path,
        string $url,
        string $mimeType,
        int $size,
        ?int $width = null,
        ?int $height = null,
        ?string $originalName = null,
    ): self {
        return new self($path, $url, $mimeType, $size, $width, $height, $originalName);
    }
}
