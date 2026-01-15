<?php

declare(strict_types=1);

namespace App\Console\Artists;

use App\Components\AppleGrab\AppleGrab;
use App\Components\AppleGrab\Entities\Album;
use App\Components\Helper;
use App\Components\SpotifyGrab\SpotifyGrab;
use App\Components\TidalGrab\TidalGrab;
use App\Modules\Command\Artist\Create;
use App\Modules\Constant;
use App\Modules\Entity\TidalToken\TidalToken;
use App\Modules\Entity\TidalToken\TidalTokenRepository;
use App\Modules\Query\GetSpotifyToken;
use App\Modules\Query\PossibleArtists\FindPossibleArtistNeedCheck;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use ZayMedia\Shared\Components\Flusher;

class PossibleArtistsCommand extends Command
{
    private const INTERVAL = 20;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FindPossibleArtistNeedCheck\Fetcher $findPossibleArtistFetcher,
        private readonly GetSpotifyToken\Fetcher $spotifyTokenFetcher,
        private readonly TidalTokenRepository $tidalTokenRepository,
        private readonly Create\Handler $handler,
        private readonly AppleGrab $appleGrab,
        private readonly SpotifyGrab $spotifyGrab,
        private readonly TidalGrab $tidalGrab,
        private readonly Flusher $flusher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('possible-artists')
            ->setDescription('Possible artists command');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Helper::bootDelay($output);

        $output->writeln('<info>Possible artists started!</info>');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $possibleArtist = $this->findPossibleArtistFetcher->fetch();

            if (null === $possibleArtist) {
                $output->writeln('<info>' . $this->time() . 'no possible artists (sleep...)</info>');
                sleep(5 * 60);
                continue;
            }

            $output->writeln('<info>' . $this->time() . '[artistId: ' . $possibleArtist->getId() . '] ' . $possibleArtist->getName() . '</info>');

            try {
                $appleId = $possibleArtist->getAppleId();

                if (null === $appleId) {
                    $output->writeln('<error>Empty apple ID</error>');
                    continue;
                }

                $appleAlbums = $this->getAppleAlbums($appleId);
                $spotifyArtistId = $this->searchSpotifyArtistId($possibleArtist->getName(), $appleAlbums);

                if (null === $spotifyArtistId) {
                    $possibleArtist->setCheckedAt(time());
                    $this->flusher->flush();

                    $output->writeln('<error>NOT FOUND (Spotify) - ' . $possibleArtist->getName() . '</error>');

                    sleep(self::INTERVAL);
                    continue;
                }

                $tidalArtistId = $this->searchTidalArtistId($possibleArtist->getName(), $appleAlbums);

                if (null === $tidalArtistId) {
                    $possibleArtist->setCheckedAt(time());
                    $this->flusher->flush();

                    $output->writeln('<error>NOT FOUND (Tidal) - ' . $possibleArtist->getName() . '</error>');

                    sleep(self::INTERVAL);
                    continue;
                }

                $appleUrl = 'https://music.apple.com/us/artist/' . $appleId;
                $spotifyUrl = 'https://open.spotify.com/artist/' . $spotifyArtistId;
                $tidalUrl = 'https://listen.tidal.com/artist/' . $tidalArtistId;

                echo PHP_EOL . 'APPLE: ' . $appleUrl;
                echo PHP_EOL . 'SPOTIFY: ' . $spotifyUrl;
                echo PHP_EOL . 'TIDAL: ' . $tidalUrl;
                echo PHP_EOL . PHP_EOL;

                try {
                    $this->handler->handle(
                        new Create\Command(
                            name: $possibleArtist->getName(),
                            unionId: null,
                            communityName: $possibleArtist->getName(),
                            description: '-',
                            categoryId: 111,
                            links: [
                                $appleUrl,
                                $spotifyUrl,
                                $tidalUrl,
                            ],
                            isAutomatic: true
                        )
                    );
                } catch (Throwable $e) {
                    echo PHP_EOL . PHP_EOL;
                    echo PHP_EOL . PHP_EOL;

                    $output->writeln('<error>' . $this->time() . $e->getMessage() . '</error>');

                    echo PHP_EOL . PHP_EOL;
                    echo PHP_EOL . PHP_EOL;
                }

                $possibleArtist->setCheckedAt(time());
                $this->flusher->flush();
            } catch (Throwable $e) {
                $output->writeln('<error>' . $this->time() . $e->getMessage() . '</error>');
            }

            sleep(self::INTERVAL);

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL) {
                break;
            }
        }
        return 0;
    }

    /**
     * @return Album[]
     * @throws Exception
     */
    private function getAppleAlbums(string $artistId): array
    {
        try {
            return $this->appleGrab->getAlbums($artistId, 100);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param Album[] $appleAlbums
     * @throws Exception
     */
    private function searchSpotifyArtistId(string $artistName, array $appleAlbums): ?string
    {
        $token = $this->spotifyTokenFetcher->fetch();

        if (null === $token) {
            throw new Exception('NO SPOTIFY ACCESS TOKEN');
        }

        $this->spotifyGrab->setAccessToken($token);

        // $countFounded = 0;

        foreach ($appleAlbums as $appleAlbum) {
            // echo PHP_EOL . PHP_EOL . 'APPLE: ' . $appleAlbum->name . ' ' . $appleAlbum->upc;

            try {
                $spotifyAlbum = $this->spotifyGrab->searchAlbum($appleAlbum->name, $appleAlbum->upc);
            } catch (Throwable) {
                $spotifyAlbum = null;
            }

            if (null !== $spotifyAlbum) {
                foreach ($spotifyAlbum->artists as $spotifyArtist) {
                    if ($spotifyArtist['name'] === $artistName) {
                        // ++$countFounded;

                        // echo PHP_EOL . 'FOUND' . PHP_EOL;

                        return $spotifyArtist['id'];
                        // if ($countFounded >= 1 || $countFounded === \count($appleAlbums)) {
                        //    return $spotifyArtist['id'];
                        // }
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param Album[] $appleAlbums
     * @throws Exception
     */
    private function searchTidalArtistId(string $artistName, array $appleAlbums): ?string
    {
        $token = $this->tidalTokenRepository->findLastActive(TidalToken::TYPE_API);

        if (null === $token) {
            throw new Exception('NO TIDAL ACCESS TOKEN');
        }

        $this->tidalGrab->setAccessToken($token->getAccessToken());

        $countFounded = 0;

        foreach ($appleAlbums as $appleAlbum) {
            // echo PHP_EOL . PHP_EOL . 'APPLE: ' . $appleAlbum->name . ' ' . $appleAlbum->upc;

            try {
                $tidalArtistId = $this->tidalGrab->searchArtistId($artistName);
            } catch (Throwable) {
                $tidalArtistId = null;
            }

            if (null === $tidalArtistId) {
                continue;
            }

            try {
                $tidalAlbums = $this->tidalGrab->getAlbums($tidalArtistId, 100);
            } catch (Throwable) {
                $tidalAlbums = [];
            }

            foreach ($tidalAlbums as $tidalAlbum) {
                if ($tidalAlbum->barcodeId === $appleAlbum->upc) {
                    ++$countFounded;

                    if ($countFounded >= 2 || $countFounded === \count($appleAlbums)) {
                        return $tidalArtistId;
                    }
                }
            }
        }

        return null;
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }
}
