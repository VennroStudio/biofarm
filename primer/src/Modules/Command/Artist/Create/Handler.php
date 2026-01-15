<?php

declare(strict_types=1);

namespace App\Modules\Command\Artist\Create;

use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Modules\Command\ArtistSocial\Create;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\Artist\ArtistRepository;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
use App\Modules\Entity\ArtistStats\ArtistStats;
use App\Modules\Entity\ArtistStats\ArtistStatsRepository;
use App\Modules\Entity\PossibleArtist\PossibleArtistRepository;
use DomainException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use ZayMedia\Shared\Components\Flusher;

use function App\Components\env;

final readonly class Handler
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private ArtistStatsRepository $artistStatsRepository,
        private PossibleArtistRepository $possibleArtistRepository,
        private Create\Handler $socialHandler,
        private Flusher $flusher,
        private RestServiceClient $restServiceClient,
    ) {}

    /** @throws Exception|GuzzleException */
    public function handle(Command $command): void
    {
        if (null !== $command->unionId) {
            $artist = $this->artistRepository->findByUnionId($command->unionId);

            if (null !== $artist) {
                throw new DomainException('Artist already exists (same unionId).');
            }
        }

        $artist = $this->artistRepository->findByDescription($command->name);

        if (null !== $artist) {
            throw new DomainException('Artist already exists (same name).');
        }

        $userId = 505;
        $unionId = $command->unionId ?? $this->getUnionId($userId, $command->communityName ?? $command->name, $command->description, $command->categoryId);

        $artist = Artist::create(
            unionId: $unionId,
            userId: $userId,
            description: $command->name,
            loName: $command->communityName ?? $command->name,
            loDescription: $command->description ?? '',
            loCategoryId: $command->categoryId ?? 111,
            isAutomatic: $command->isAutomatic
        );

        $this->artistRepository->add($artist);
        $this->flusher->flush();

        $artistStats = ArtistStats::create($artist->getId());
        $this->artistStatsRepository->add($artistStats);
        $this->flusher->flush();

        $this->createSocials($artist->getId(), $command->links);
    }

    /** @param string[] $links */
    private function createSocials(int $artistId, array $links): void
    {
        foreach ($links as $link) {
            if (empty($link)) {
                continue;
            }

            try {
                $artistSocial = $this->socialHandler->handle(
                    new Create\Command($artistId, $link, null)
                );

                $possibleArtist = null;

                if ($artistSocial->getType() === ArtistSocial::TYPE_SPOTIFY) {
                    $possibleArtist = $this->possibleArtistRepository->findBySpotifyId($artistSocial->getIdByUrl());
                } elseif ($artistSocial->getType() === ArtistSocial::TYPE_APPLE) {
                    $possibleArtist = $this->possibleArtistRepository->findByAppleId($artistSocial->getIdByUrl());
                } elseif ($artistSocial->getType() === ArtistSocial::TYPE_TIDAL) {
                    $possibleArtist = $this->possibleArtistRepository->findByTidalId($artistSocial->getIdByUrl());
                }

                if (null !== $possibleArtist && null === $possibleArtist->getArtistId()) {
                    $possibleArtist->setArtistId($artistId);
                    $this->flusher->flush();
                }
            } catch (Exception) {
            }
        }
    }

    /** @throws Exception|GuzzleException */
    private function getUnionId(int $userId, string $name, ?string $description, ?int $categoryId): int
    {
        $accessToken = AccessToken::for((string)$userId);

        $response = $this->restServiceClient->post(
            url: env('HOST_API_LO') . '/v1/communities/musical',
            body: [
                'name'          => $name,
                'description'   => $description,
                'categoryId'    => $categoryId ?? 111,
                'website'       => null,
                'photoHost'     => null,
                'photoFileId'   => null,
            ],
            accessToken: $accessToken
        );

        if (isset($response['data']['id'])) {
            return (int)$response['data']['id'];
        }

        throw new Exception('Can not create community.');
    }
}
