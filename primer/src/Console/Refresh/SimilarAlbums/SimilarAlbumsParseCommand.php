<?php

declare(strict_types=1);

namespace App\Console\Refresh\SimilarAlbums;

use App\Components\SpotifyGrab\SpotifyGrab;
use App\Components\TidalGrab\TidalGrab;
use App\Modules\Constant;
use App\Modules\Entity\TidalAlbum\TidalAlbumRepository;
use App\Modules\Entity\TidalToken\TidalToken;
use App\Modules\Entity\TidalToken\TidalTokenRepository;
use App\Modules\Query\Album\FindAlbumNeedSimilarUpdate;
use App\Modules\Query\GetAlbumTrackSpotifyIds;
use App\Modules\Query\GetSpotifyToken;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SimilarAlbumsParseCommand extends Command
{
    private const INTERVAL = 5;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FindAlbumNeedSimilarUpdate\Fetcher $findAlbumFetcher,
        private readonly TidalAlbumRepository $tidalAlbumRepository,
        private readonly SpotifyGrab $spotifyGrab,
        private readonly TidalGrab $tidalGrab,
        private readonly TidalTokenRepository $tidalTokenRepository,
        private readonly GetAlbumTrackSpotifyIds\Fetcher $albumTrackSpotifyIdsFetcher,
        private readonly GetSpotifyToken\Fetcher $spotifyTokenFetcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('similar-albums-parse')
            ->setDescription('Similar albums parse command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Parse similar albums started!</info>');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $album = $this->findAlbumFetcher->fetch();

            if (null === $album) {
                $output->writeln('<info>' . $this->time() . 'no albums (sleep...)</info>');
                sleep(60);
                continue;
            }

            try {
                $output->writeln('<info>' . $this->time() . '[albumId: ' . $album->getId() . '] ' . $album->getSpotifyName() . '</info>');

                $tidalIds = [];
                $spotifyIds = [];

                if (null !== $album->getTidalAlbumId()) {
                    $tidalAlbum = $this->tidalAlbumRepository->getById($album->getTidalAlbumId());
                    $tidalIds = $this->getTidalSimilarAlbumIds($tidalAlbum->getTidalId());
                }

                if (null !== $album->getSpotifyId()) {
                    $trackSpotifyIds = $this->albumTrackSpotifyIdsFetcher->fetch(
                        new GetAlbumTrackSpotifyIds\Query($album->getId())
                    );
                    $spotifyIds = $this->getSpotifySimilarAlbumIds($album->getArtistIds(), $trackSpotifyIds);
                }

                if (!empty($tidalIds)) {
                    $album->setSimilarTidalIds(json_encode($tidalIds));
                }

                if (!empty($spotifyIds)) {
                    $album->setSimilarSpotifyIds(json_encode($spotifyIds));
                }

                $album->setSimilarChecked();

                $this->em->flush();
            } catch (Throwable $e) {
                $output->writeln('<error>' . $this->time() . $e->getMessage() . '</error>');
                sleep(5 * 60);
                continue;
            }

            // $output->writeln('<info>sleep ' . self::INTERVAL . ' sec...</info>');
            sleep(self::INTERVAL);

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL) {
                break;
            }
        }

        return 0;
    }

    /**
     * @param string[] $artistIds
     * @param string[] $trackIds
     * @return string[]|null
     */
    private function getSpotifySimilarAlbumIds(array $artistIds, array $trackIds): ?array
    {
        $this->refreshSpotifyAccessToken();
        return $this->spotifyGrab->getSimilarAlbumIds($artistIds, $trackIds);
    }

    private function refreshSpotifyAccessToken(): void
    {
        while (true) {
            $accessToken = $this->spotifyTokenFetcher->fetch();

            if (null !== $accessToken) {
                $this->spotifyGrab->setAccessToken($accessToken);
                return;
            }

            echo PHP_EOL . 'NO ACCESS TOKENS!' . PHP_EOL;
            sleep(Constant::SLEEP_NO_ACCESS_TOKEN);
        }
    }

    /**
     * @return string[]|null
     * @throws Exception
     */
    private function getTidalSimilarAlbumIds(string $albumId): ?array
    {
        $this->refreshTidalAccessToken();
        return $this->tidalGrab->getSimilarAlbumIds($albumId);
    }

    private function refreshTidalAccessToken(): void
    {
        while (true) {
            $accessToken = $this->tidalTokenRepository->findLastActive(TidalToken::TYPE_API);

            if (null === $accessToken) {
                echo PHP_EOL . 'NO ACCESS TOKENS!' . PHP_EOL;
                sleep(Constant::SLEEP_NO_ACCESS_TOKEN);

                continue;
            }

            try {
                if (
                    $accessToken->isExpired() &&
                    null !== $accessToken->getClientId() &&
                    null !== $accessToken->getClientSecret()
                ) {
                    $token = $this->tidalGrab->auth($accessToken->getClientId(), $accessToken->getClientSecret());

                    $accessToken->refresh($token->accessToken);
                    $this->tidalTokenRepository->add($accessToken);
                    $this->em->flush();
                }
            } catch (Throwable) {
                echo PHP_EOL . 'FAILED REFRESH ACCESS TOKEN!' . PHP_EOL;
                sleep(Constant::SLEEP_NO_ACCESS_TOKEN);

                continue;
            }

            $this->tidalGrab->setAccessToken($accessToken->getAccessToken());
            $this->tidalGrab->setCountryCode('US');
            $this->tidalGrab->setDelay(10);

            return;
        }
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }
}
