<?php

declare(strict_types=1);

namespace App\Http\Action\V1;

use App\Modules\Entity\Album\AlbumRepository;
use App\Modules\Entity\Artist\ArtistRepository;
use App\Modules\Entity\PossibleArtist\PossibleArtistRepository;
use App\Modules\Entity\Track\TrackRepository;
use App\Modules\Query\Stats\Artists\FetcherCount;
use App\Modules\Query\Stats\Artists\Query;
use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use ZayMedia\Shared\Http\Response\JsonResponse;

final readonly class GetStatsAction implements RequestHandlerInterface
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private PossibleArtistRepository $possibleArtistRepository,
        private AlbumRepository $albumRepository,
        private TrackRepository $trackRepository,
        private FetcherCount $fetcherCount,
    ) {}

    /** @throws Exception */
    public function handle(Request $request): Response
    {
        return new JsonResponse([
            'countArtists'          => $this->artistRepository->getCount(),
            'countArtistsSuggest'   => $this->possibleArtistRepository->getCount(),
            'countArtistsFullScan'  => $this->fetcherCount->fetch($this->getQuery(0)),
            'countArtistsChecking'  => $this->fetcherCount->fetch($this->getQuery(3)),
            'countArtistsUpload'    => $this->fetcherCount->fetch($this->getQuery(4)),
            'countAlbumsMapped'     => $this->albumRepository->getCountMapped(),
            'countAlbumsNotLoaded'  => $this->albumRepository->getCountNotLoaded(),
            'countTracksMapped'     => $this->trackRepository->getCountMapped(),
            'countTracksNotLoaded'  => $this->trackRepository->getCountNotLoaded(),
        ]);
    }

    private function getQuery(int $type): Query
    {
        return new Query(
            type: $type,
        );
    }
}
