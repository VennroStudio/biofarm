<?php

declare(strict_types=1);

namespace App\Console;

use App\Modules\Entity\Album\Album;
use App\Modules\Entity\AppleAlbum\AppleAlbum;
use App\Modules\Entity\AppleTrack\AppleTrack;
use App\Modules\Entity\Artist\ArtistRepository;
use App\Modules\Entity\TidalAlbum\TidalAlbum;
use App\Modules\Entity\TidalTrack\TidalTrack;
use App\Modules\Entity\Track\Track;
use App\Modules\Query\Artists\GetUnionIdsBySpotifyIds;
use App\Modules\Query\GetAlbumArtists;
use Exception;

readonly class HelperData
{
    public function __construct(
        private GetAlbumArtists\Fetcher $getAlbumArtistsFetcher,
        private GetUnionIdsBySpotifyIds\Fetcher $getUnionIdsBySpotifyIdsFetcher,
        private ArtistRepository $artistRepository,
    ) {}

    /** @throws Exception */
    public function album(
        Album $album,
        TidalAlbum $tidalAlbum,
        ?AppleAlbum $appleAlbum,
        bool $withUnionNumbers
    ): array {
        $upcOther = [];

        if (
            $tidalAlbum->getBarcodeId() !== $album->getSpotifyUPC() &&
            !\in_array($tidalAlbum->getBarcodeId(), $upcOther, true)
        ) {
            $upcOther[] = $tidalAlbum->getBarcodeId();
        }

        $appleAlbumUpc = $appleAlbum?->getUpc() ?? null;
        if (
            $appleAlbumUpc !== $album->getSpotifyUPC() &&
            null !== $appleAlbumUpc && !\in_array($appleAlbumUpc, $upcOther, true)
        ) {
            $upcOther[] = $appleAlbumUpc;
        }

        if (mb_strtolower($album->getSpotifyType(), 'UTF-8') === 'compilation') {
            $type = 'compilation';
        } else {
            $type = $tidalAlbum->getType();
        }

        $unionNumbers = [];

        if ($withUnionNumbers) {
            $albumArtists = $this->getAlbumArtistsFetcher->fetch(
                new GetAlbumArtists\Query($album->getId())
            );

            foreach ($albumArtists as $albumArtist) {
                $artist = $this->artistRepository->getById($albumArtist->getArtistId());

                $unionNumbers[] = [
                    'unionId' => $artist->getUnionId(),
                    'number' => $albumArtist->getNumber(),
                ];
            }
        }

        return [
            'name'                  => $album->getSpotifyName(),
            'upc'                   => $album->getSpotifyUPC(),
            'upcOther'              => $upcOther,
            'type'                  => $type,
            'artists'               => $album->getSeparateArtists(),
            'label'                 => $album->getSpotifyLabel(),
            'isExplicit'            => $tidalAlbum->isExplicit(),
            'releasedAt'            => $album->getSpotifyReleasedAt(),
            'releasedAtPrecision'   => $album->getSpotifyReleasedAtPrecision(),
            'unionNumbers'          => $unionNumbers,
            'genres'                => $appleAlbum?->getGenreNames() ?? [],
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     isrc: string|null,
     *     isrcOther: string[],
     *     diskNumber: int,
     *     trackNumber: int,
     *     artists: string,
     *     duration: int,
     *     isExplicit: bool,
     *     isDolbyAtmos: bool,
     *     unionNumbers: array{
     *         unionId: int,
     *         number: int
     *     }[],
     *     genres: string[]
     * }
     * @throws Exception
     */
    public function audio(
        Track $track,
        TidalTrack $tidalTrack,
        ?AppleTrack $appleTrack,
        bool $withUnionNumbers
    ): array {
        /** @var string[] $isrcOther */
        $isrcOther = [];

        if (
            $tidalTrack->getIsrc() !== $track->getSpotifyISRC() &&
            !\in_array($tidalTrack->getIsrc(), $isrcOther, true)
        ) {
            $isrcOther[] = $tidalTrack->getIsrc();
        }

        $appleTrackIsrc = $appleTrack?->getIsrc() ?? null;
        if (
            $appleTrackIsrc !== $track->getSpotifyISRC() &&
            null !== $appleTrackIsrc && !\in_array($appleTrackIsrc, $isrcOther, true)
        ) {
            $isrcOther[] = $appleTrackIsrc;
        }

        $unionNumbers = [];

        if ($withUnionNumbers) {
            $spotifyIds = $track->getArtistIds();

            $items = $this->getUnionIdsBySpotifyIdsFetcher->fetch(
                new GetUnionIdsBySpotifyIds\Query($spotifyIds)
            );

            $number = 1;

            foreach ($spotifyIds as $spotifyId) {
                foreach ($items as $item) {
                    if (!str_contains($item['url'], $spotifyId)) {
                        continue;
                    }

                    $unionNumbers[] = [
                        'unionId' => $item['unionId'],
                        'number'  => $number,
                    ];
                }

                ++$number;
            }
        }

        $name = $track->getSpotifyName();

        if (empty($name)) {
            $name = $tidalTrack->getName();
        }

        return [
            'name'          => $name,
            'isrc'          => $track->getSpotifyISRC(),
            'isrcOther'     => $isrcOther,
            'diskNumber'    => $track->getSpotifyDiskNumber(),
            'trackNumber'   => $track->getSpotifyTrackNumber(),
            'artists'       => $track->getSeparateArtists(),
            'duration'      => $track->getSpotifyDuration(),
            'isExplicit'    => $track->isSpotifyExplicit(),
            'isDolbyAtmos'  => $tidalTrack->isDolbyAtmos(),
            'unionNumbers'  => $unionNumbers,
            'genres'        => $appleTrack?->getGenreNames() ?? [],
        ];
    }
}
