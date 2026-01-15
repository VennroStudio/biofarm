<?php

declare(strict_types=1);

namespace App\Modules\Entity\PlaylistTranslate;

use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Exception;

#[ORM\Entity]
#[ORM\Table(name: 'playlist_translate')]
#[ORM\Index(fields: ['playlistId', 'lang'], name: 'IDX_PLAYLIST')]
class PlaylistTranslate
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', unique: true)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $playlistId;

    #[ORM\Column(type: 'string', length: 10)]
    private string $lang;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $photo;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $photoHost;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $photoFileId;

    #[ORM\Column(type: 'string', length: 400)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description;

    private function __construct(
        int $playlistId,
        string $lang,
        string $name,
        string $description,
        ?string $photo,
        ?string $photoHost,
        ?string $photoFileId
    ) {
        $this->playlistId = $playlistId;
        $this->lang = $lang;
        $this->name = $name;
        $this->description = $description;
        $this->photo = $photo;
        $this->photoHost = $photoHost;
        $this->photoFileId = $photoFileId;
    }

    public static function create(
        int $playlistId,
        string $lang,
        string $name,
        string $description,
        ?string $photo = null,
        ?string $photoHost = null,
        ?string $photoFileId = null
    ): self {
        return new self(
            playlistId: $playlistId,
            lang: $lang,
            name: $name,
            description: $description,
            photo: $photo,
            photoHost: $photoHost,
            photoFileId: $photoFileId
        );
    }

    /** @throws Exception */
    public function edit(
        string $name,
        string $description,
        ?string $photo = null,
        ?string $photoHost = null,
        ?string $photoFileId = null
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->photo = $photo;
        $this->photoHost = $photoHost;
        $this->photoFileId = $photoFileId;
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

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): void
    {
        $this->lang = $lang;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): void
    {
        $this->photo = $photo;
    }

    public function getPhotoHost(): ?string
    {
        return $this->photoHost;
    }

    public function setPhotoHost(?string $photoHost): void
    {
        $this->photoHost = $photoHost;
    }

    public function getPhotoFileId(): ?string
    {
        return $this->photoFileId;
    }

    public function setPhotoFileId(?string $photoFileId): void
    {
        $this->photoFileId = $photoFileId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->getId(),
            'playlist_id'   => $this->getPlaylistId(),
            'lang'          => $this->getLang(),
            'photo'         => $this->getPhoto(),
            'name'          => $this->getName(),
            'description'   => $this->getDescription(),
        ];
    }
}
