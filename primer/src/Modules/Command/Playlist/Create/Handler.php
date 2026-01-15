<?php

declare(strict_types=1);

namespace App\Modules\Command\Playlist\Create;

use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Modules\Entity\Playlist\Playlist;
use App\Modules\Entity\Playlist\PlaylistRepository;
use DomainException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Throwable;
use ZayMedia\Shared\Components\Flusher;

use function App\Components\env;

final readonly class Handler
{
    private string $host;

    public function __construct(
        private PlaylistRepository $playlistRepository,
        private RestServiceClient $restServiceClient,
        private Flusher $flusher,
    ) {
        $this->host = env('HOST_API_LO');
    }

    /** @throws GuzzleException|Throwable */
    public function handle(Command $command): Playlist
    {
        $playlist = $this->playlistRepository->findByUrl($command->url);

        if (null !== $playlist) {
            throw new DomainException('Playlist already exists.');
        }

        $playlist = Playlist::create(
            unionId: $command->unionId,
            userId: $command->userId,
            name: $command->name,
            url: $command->url,
            isFollowed: $command->isFollowed
        );

        $this->playlistRepository->add($playlist);
        $this->flusher->flush();

        $loPlaylistId = $this->createPlaylistLO($command);

        $playlist->setLoPlaylistId($loPlaylistId);
        $this->flusher->flush();

        return $playlist;
    }

    /** @throws Exception|GuzzleException|Throwable */
    private function createPlaylistLO(Command $command): ?int
    {
        $accessToken = AccessToken::for((string)$command->userId);

        $result = $this->restServiceClient->post(
            url: $this->host . '/v1/audio-playlists',
            body: [
                'unionId'       => $command->unionId,
                'name'          => $command->name,
                'translates'    => [],
            ],
            accessToken: $accessToken
        );

        if (!isset($result['data']['id'])) {
            return null;
        }

        return (int)$result['data']['id'];
    }
}
