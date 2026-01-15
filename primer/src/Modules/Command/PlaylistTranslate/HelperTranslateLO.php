<?php

declare(strict_types=1);

namespace App\Modules\Command\PlaylistTranslate;

use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Modules\Entity\Playlist\Playlist;
use App\Modules\Entity\Playlist\PlaylistRepository;
use App\Modules\Entity\PlaylistTranslate\PlaylistTranslate;
use Exception;
use Throwable;

use function App\Components\env;

final readonly class HelperTranslateLO
{
    private string $host;

    public function __construct(
        private PlaylistRepository $playlistRepository,
        private RestServiceClient $restServiceClient,
    ) {
        $this->host = env('HOST_API_LO');
    }

    public function saveToLO(Playlist $playlist, PlaylistTranslate $translate): void
    {
        $accessToken = AccessToken::for((string)$playlist->getUserId());

        $loPlaylistId = $playlist->getLoPlaylistId();

        if (null === $loPlaylistId) {
            return;
        }

        $this->restServiceClient->post(
            url: $this->host . '/v1/audio-playlists/' . $loPlaylistId . '/translates/' . $translate->getLang(),
            body: [
                'name'          => $translate->getName(),
                'description'   => $translate->getDescription(),
                'photoHost'     => $translate->getPhotoHost(),
                'photoFileId'   => $translate->getPhotoFileId(),
            ],
            accessToken: $accessToken
        );
    }

    public function deleteFromLO(Playlist $playlist, string $lang): void
    {
        $accessToken = AccessToken::for((string)$playlist->getUserId());

        $loPlaylistId = $playlist->getLoPlaylistId();

        if (null === $loPlaylistId) {
            return;
        }

        $this->restServiceClient->delete(
            url: $this->host . '/v1/audio-playlists/' . $loPlaylistId . '/translates/' . $lang,
            body: [],
            accessToken: $accessToken
        );
    }

    /**
     * @return array{host: string, file_id: string}|null
     * @throws Throwable
     */
    public function photoData(int $playlistId, string $path): ?array
    {
        $playlist = $this->playlistRepository->findById($playlistId);

        if (null === $playlist) {
            return null;
        }

        $accessToken = AccessToken::for((string)$playlist->getUserId());
        $photoServer = $this->getPhotoServer($accessToken);

        if (null === $photoServer) {
            return null;
        }

        $upload = $this->restServiceClient->sendFile($photoServer, $path, ['union_id' => $playlist->getUnionId()]);

        if (!isset($upload['response']['host'], $upload['response']['file_id'])) {
            return null;
        }

        return [
            'host'      => (string)$upload['response']['host'],
            'file_id'   => (string)$upload['response']['file_id'],
        ];
    }

    /** @throws Throwable */
    private function getPhotoServer(string $accessToken): ?string
    {
        try {
            $response = $this->restServiceClient->get(
                url: $this->host . '/v1/audio-playlists/photo-server',
                accessToken: $accessToken
            );

            if (isset($response['data']['url'])) {
                return (string)$response['data']['url'];
            }
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }

        return null;
    }
}
