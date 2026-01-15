<?php

declare(strict_types=1);

namespace App\Modules\Command\Spotify\RefreshAlbum;

use App\Modules\Command\Album\AddArtist;
use App\Modules\Entity\Album\Album;
use App\Modules\Entity\Album\AlbumRepository;
use App\Modules\Entity\AlbumArtist\AlbumArtistRepository;
use Exception;
use ZayMedia\Shared\Components\Flusher;

final readonly class Handler
{
    public function __construct(
        private AlbumRepository $albumRepository,
        private AlbumArtistRepository $albumArtistRepository,
        private AddArtist\Handler $addArtistHandler,
        private Flusher $flusher,
    ) {}

    /** @throws Exception */
    public function handle(Command $command): Album
    {
        $album = $this->albumRepository->findBySpotifyId($command->albumId);

        if (null === $album) {
            $album = Album::createSpotify(
                id: $command->albumId,
                type: $command->type,
                name: $command->name,
                totalTracks: $command->totalTracks,
                upc: $command->upc
            );
        }

        $album->editSpotifyInfo(
            type: $command->type,
            name: $command->name,
            releasedAt: $command->releasedAt,
            releasedPrecision: $command->releasedPrecision,
            totalTracks: $command->totalTracks,
            availableMarkets: $command->availableMarkets,
            artists: $command->artists,
            upc: $command->upc,
            images: $command->images,
            copyrights: $command->copyrights,
            externalIds: $command->externalIds,
            genres: $command->genres,
            label: $command->label,
            popularity: $command->popularity
        );

        $album->setUpdatedAt(time());

        $this->albumRepository->add($album);
        $this->flusher->flush();

        if (null !== $command->artistId) {
            $this->checkArtist($album, $command->artistId, $command->spotifyArtistIds);
        }

        return $album;
    }

    /** @param string[] $spotifyArtistIds */
    private function checkArtist(Album $album, int $artistId, array $spotifyArtistIds): void
    {
        $number = $album->getArtistNumber($spotifyArtistIds);

        $albumArtist = $this->albumArtistRepository->findByAlbumAndArtistIds($album->getId(), $artistId);

        if (null !== $albumArtist) {
            if ($albumArtist->getNumber() !== $number) {
                $albumArtist->setNumber($number);
                $this->flusher->flush();
            }
            return;
        }

        $this->addArtistHandler->handle(
            new AddArtist\Command(
                albumId: $album->getId(),
                artistId: $artistId,
                number: $number,
            )
        );
    }
}
