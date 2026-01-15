<?php

declare(strict_types=1);

namespace App\Console\Loader;

use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Modules\Entity\Album\Album;
use App\Modules\Entity\AlbumArtist\AlbumArtistRepository;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\Track\Track;
use App\Modules\Query\GetSpotifySocialIds;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function App\Components\env;

readonly class AlbumLoader
{
    private string $host;

    public function __construct(
        private EntityManagerInterface $em,
        private AlbumArtistRepository $albumArtistRepository,
        private AlbumCreator $albumCreator,
        private TrackLoader $trackLoader,
        private GetSpotifySocialIds\Fetcher $getSpotifySocialIdsFetcher,
        private RestServiceClient $restServiceClient,
    ) {
        $this->host = env('HOST_API_LO');
    }

    /** @throws Throwable */
    public function handle(Artist $artist, Album $album, OutputInterface $output): void
    {
        $spotifySocialIds = $this->getSpotifySocialIdsFetcher->fetch(
            new GetSpotifySocialIds\Query($artist->getId())
        );

        $loAlbumId = $album->getLoAlbumId() ?? $this->albumCreator->handle($artist, $album, $spotifySocialIds);
        $loadedAlbum = $this->albumArtistRepository->findFirstLoadedByAlbumId($album->getId());

        if (null !== $loadedAlbum) {
            $accessToken = AccessToken::for((string)$artist->getUserId());

            if ($loadedAlbum->getArtistId() !== $artist->getId()) {
                $unionNumber = $album->getArtistNumber($spotifySocialIds);
                $this->addArtistToAlbum($accessToken, $artist, $loAlbumId, $unionNumber);
            }

            $this->addArtistToTracks($accessToken, $artist, $spotifySocialIds, $album->getId());
        }

        $tracks = $this->getTracksByAlbumId($album->getId());

        foreach ($tracks as $track) {
            $this->trackLoader->handle($artist, $spotifySocialIds, $track, $loAlbumId, $output);
        }
    }

    /** @return Track[] */
    private function getTracksByAlbumId(int $albumId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from(Track::class, 't')
            ->andWhere('t.albumId = :albumId')
            ->andWhere('t.loTrackId IS NULL')
            ->setParameter('albumId', $albumId)
            ->orderBy('t.id', 'ASC');

        /** @var Track[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($tracks as $track) {
            $items[] = $track;
        }

        return $items;
    }

    /**
     * @param string[] $spotifySocialIds
     * @throws Exception|GuzzleException
     */
    private function addArtistToTracks(string $accessToken, Artist $artist, array $spotifySocialIds, int $albumId): void
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from(Track::class, 't')
            ->andWhere('t.albumId = :albumId')
            ->setParameter('albumId', $albumId)
            ->orderBy('t.id', 'ASC');

        /** @var Track[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        foreach ($tracks as $track) {
            $number = $track->getArtistNumber($spotifySocialIds);

            if (null === $number) {
                continue;
            }

            $loTrackId = $track->getLoTrackId();

            if (null !== $loTrackId) {
                $this->addArtistToTrack($accessToken, $artist, $loTrackId, $number);
            }
        }
    }

    /** @throws Exception|GuzzleException */
    private function addArtistToAlbum(string $accessToken, Artist $artist, int $albumId, ?int $unionNumber): void
    {
        $response = $this->restServiceClient->post(
            url: $this->host . '/v1/audio-albums/' . $albumId . '/artist',
            body: [
                'unionId'       => $artist->getUnionId(),
                'unionNumber'   => $unionNumber,
            ],
            accessToken: $accessToken
        );

        if (!isset($response['data']['success'])) {
            throw new Exception('Can not add union to album');
        }
    }

    /** @throws Exception|GuzzleException */
    private function addArtistToTrack(string $accessToken, Artist $artist, int $audioId, int $unionNumber): void
    {
        $response = $this->restServiceClient->post(
            url: $this->host . '/v1/audios/' . $audioId . '/artist',
            body: [
                'unionId'       => $artist->getUnionId(),
                'unionNumber'   => $unionNumber,
            ],
            accessToken: $accessToken
        );

        if (!isset($response['data']['success'])) {
            throw new Exception('Can not add union to audio');
        }
    }
}
