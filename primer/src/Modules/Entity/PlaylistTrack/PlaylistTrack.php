<?php

declare(strict_types=1);

namespace App\Modules\Entity\PlaylistTrack;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: 'playlist_track')]
class PlaylistTrack
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $playlistId;

    #[ORM\Column(type: 'integer')]
    private int $trackId;

    #[ORM\Column(type: 'integer')]
    private int $number;

    #[ORM\Column(name: 'created_at', type: 'integer', nullable: true)]
    private ?int $createdAt = null;

    private function __construct(
        int $playlistId,
        int $trackId,
        int $number,
    ) {
        $this->playlistId = $playlistId;
        $this->trackId = $trackId;
        $this->number = $number;
    }

    public static function create(
        int $playlistId,
        int $trackId,
        int $number,
    ): self {
        $item = new self(
            playlistId: $playlistId,
            trackId: $trackId,
            number: $number,
        );

        $item->createdAt = time();

        return $item;
    }

    public function getId(): int
    {
        if (null === $this->id) {
            throw new DomainException('Id not set');
        }
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getPlaylistId(): int
    {
        return $this->playlistId;
    }

    public function setPlaylistId(int $playlistId): void
    {
        $this->playlistId = $playlistId;
    }

    public function getTrackId(): int
    {
        return $this->trackId;
    }

    public function setTrackId(int $trackId): void
    {
        $this->trackId = $trackId;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
