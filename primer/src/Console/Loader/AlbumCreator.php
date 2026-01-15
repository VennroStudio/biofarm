<?php

declare(strict_types=1);

namespace App\Console\Loader;

use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Console\HelperData;
use App\Modules\Entity\Album\Album;
use App\Modules\Entity\AppleAlbum\AppleAlbum;
use App\Modules\Entity\AppleAlbum\AppleAlbumRepository;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\TidalAlbum\TidalAlbum;
use App\Modules\Entity\TidalAlbum\TidalAlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Throwable;

use function App\Components\env;

readonly class AlbumCreator
{
    private string $host;

    public function __construct(
        private EntityManagerInterface $em,
        private TidalAlbumRepository $tidalAlbumRepository,
        private AppleAlbumRepository $appleAlbumRepository,
        private RestServiceClient $restServiceClient,
        private AlbumCoverLoader $albumCoverLoader,
        private HelperData $helperData,
    ) {
        $this->host = env('HOST_API_LO');
    }

    /**
     * @param string[] $spotifySocialIds
     * @throws Throwable
     */
    public function handle(Artist $artist, Album $album, array $spotifySocialIds): int
    {
        $accessToken = $this->getAccessToken($artist->getUserId());

        $tidalAlbumId = $album->getTidalAlbumId();
        if (null === $tidalAlbumId) {
            throw new Exception('Empty tidal album ID');
        }

        $unionNumber = $album->getArtistNumber($spotifySocialIds);
        $tidalAlbum = $this->tidalAlbumRepository->getById($tidalAlbumId);
        $appleAlbum = null !== $album->getAppleAlbumId() ? $this->appleAlbumRepository->findById($album->getAppleAlbumId()) : null;

        $loAlbumId = $this->createAlbumLO($artist->getUnionId(), $unionNumber, $album, $tidalAlbum, $appleAlbum, $accessToken);

        $album->setLoAlbumId($loAlbumId);
        $this->em->flush();

        $this->albumCoverLoader->handle($artist->getUnionId(), $loAlbumId, $accessToken, $album->getSpotifyImages());

        return $loAlbumId;
    }

    /** @throws Throwable */
    private function createAlbumLO(
        int $unionId,
        ?int $unionNumber,
        Album $album,
        TidalAlbum $tidalAlbum,
        ?AppleAlbum $appleAlbum,
        string $accessToken
    ): int {
        $response = $this->restServiceClient->post(
            url: $this->host . '/v1/audio-albums',
            body: [
                'unionId'               => $unionId,
                'unionNumber'           => $unionNumber,
                ...$this->helperData->album($album, $tidalAlbum, $appleAlbum, false),
            ],
            accessToken: $accessToken
        );

        if (isset($response['data']['id'])) {
            return (int)$response['data']['id'];
        }

        throw new Exception('Can not create LO album');
    }

    /** @throws Exception */
    private function getAccessToken(int $userId): string
    {
        return AccessToken::for((string)$userId);
    }
}
