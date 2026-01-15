<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\Update;

use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\Artist\ArtistRepository;
use Throwable;
use ZayMedia\Shared\Components\Flusher;

use function App\Components\env;

final readonly class Handler
{
    private string $host;

    public function __construct(
        private ArtistRepository $artistRepository,
        private Flusher $flusher,
        private RestServiceClient $restServiceClient,
    ) {
        $this->host = env('HOST_API_LO');
    }

    /** @throws Throwable */
    public function handle(Command $command): void
    {
        $artist = $this->artistRepository->getById($command->artistId);

        $artist->edit(
            description: $command->description,
            loName: $command->loName,
            loDescription: $command->loDescription,
            loCategoryId: $command->loCategoryId
        );

        $this->flusher->flush();

        $this->saveToLO($artist);
    }

    private function saveToLO(Artist $artist): void
    {
        $accessToken = AccessToken::for((string)$artist->getUserId());

        $response = $this->restServiceClient->get(
            url: $this->host . '/v1/unions/' . $artist->getUnionId(),
            accessToken: $accessToken
        );

        if (!isset($response['data'])) {
            return;
        }

        /** @var array{description: string, website: string, status: string, ageLimit: int, cityId: int} $data */
        $data = $response['data'];

        $this->restServiceClient->put(
            url: $this->host . '/v1/communities/' . $artist->getUnionId(),
            body: [
                'name'          => $artist->getLoName(),
                'categoryId'    => $artist->getLoCategoryId(),
                'description'   => $artist->getLoDescription(),
                'website'       => $data['website'],
                'status'        => $data['status'],
                'ageLimit'      => $data['ageLimit'],
                'cityId'        => $data['cityId'],
            ],
            accessToken: $accessToken
        );
    }
}
