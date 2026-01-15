<?php

declare(strict_types=1);

namespace App\Console\Refresh\RateArtists;

use App\Components\SpotifyGrab\SpotifyGrab;
use App\Components\TidalGrab\TidalGrab;
use App\Modules\Constant;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
use App\Modules\Entity\TidalToken\TidalToken;
use App\Modules\Entity\TidalToken\TidalTokenRepository;
use App\Modules\Query\FindArtistSocialNeedSimilarUpdate;
use App\Modules\Query\GetSpotifyToken;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class RateArtistsParseCommand extends Command
{
    private const INTERVAL = 5;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FindArtistSocialNeedSimilarUpdate\Fetcher $findArtistSocialFetcher,
        private readonly SpotifyGrab $spotifyGrab,
        private readonly TidalGrab $tidalGrab,
        private readonly TidalTokenRepository $tidalTokenRepository,
        private readonly GetSpotifyToken\Fetcher $spotifyTokenFetcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('rate-artists-parse')
            ->setDescription('Rate artists parse command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Parse rate artists started!</info>');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $social = $this->findArtistSocialFetcher->fetch();

            if (null === $social) {
                $output->writeln('<info>' . $this->time() . 'no socials (sleep...)</info>');
                sleep(60);
                continue;
            }

            try {
                $output->writeln('<info>' . $this->time() . '[artistId: ' . $social->getArtistId() . '] id: ' . $social->getId() . '</info>');

                /** @var string[] $socialIds */
                $socialIds = [];
                /** @var string[] $popularTrackIds */
                $popularTrackIds = [];

                if ($social->getType() === ArtistSocial::TYPE_SPOTIFY) {
                    $socialIds = $this->getSpotifySimilarArtistIds($social->getIdByUrl());
                    $popularTrackIds = $this->getSpotifyArtistTopTracks($social->getIdByUrl());
                } elseif ($social->getType() === ArtistSocial::TYPE_TIDAL) {
                    $socialIds = $this->getTidalSimilarArtistIds($social->getIdByUrl());
                    $popularTrackIds = $this->getTidalArtistTopTracks($social->getIdByUrl());
                }

                if (!empty($socialIds)) {
                    $social->setSimilarIds(json_encode($socialIds));
                }

                $social->setPopularTrackIds(!empty($popularTrackIds) ? json_encode($popularTrackIds) : null);
                $social->setRateChecked();

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

    /** @return string[]|null */
    private function getSpotifySimilarArtistIds(string $artistId): ?array
    {
        $this->refreshSpotifyAccessToken();
        return $this->spotifyGrab->getSimilarArtistIds($artistId);
    }

    /** @return string[]|null */
    private function getSpotifyArtistTopTracks(string $artistId): ?array
    {
        $this->refreshSpotifyAccessToken();
        return $this->spotifyGrab->getArtistTopTracks($artistId);
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
    private function getTidalSimilarArtistIds(string $artistId): ?array
    {
        $this->refreshTidalAccessToken();
        return $this->tidalGrab->getSimilarArtistIds($artistId);
    }

    /**
     * @return string[]|null
     * @throws Exception
     */
    private function getTidalArtistTopTracks(string $artistId): ?array
    {
        $this->refreshTidalAccessToken();
        return $this->tidalGrab->getArtistTopTracks($artistId);
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
