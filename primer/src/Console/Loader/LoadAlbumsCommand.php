<?php

declare(strict_types=1);

namespace App\Console\Loader;

use App\Components\Helper;
use App\Components\TidalGrab\TidalDL;
use App\Modules\Command\Artist\UpdateStatsLoaded;
use App\Modules\Constant;
use App\Modules\Entity\Album\AlbumRepository;
use App\Modules\Entity\AlbumArtist\AlbumArtistRepository;
use App\Modules\Entity\Artist\ArtistRepository;
use App\Modules\Entity\TidalToken\TidalToken;
use App\Modules\Entity\TidalToken\TidalTokenRepository;
use App\Modules\Query\Album\FindAlbumNeedLoaded;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class LoadAlbumsCommand extends Command
{
    private const INTERVAL = 60;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AlbumRepository $albumRepository,
        private readonly AlbumArtistRepository $albumArtistRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly TidalTokenRepository $tidalTokenRepository,
        private readonly AlbumLoader $albumLoader,
        private readonly TidalDL $tidalDL,
        private readonly FindAlbumNeedLoaded\Fetcher $findNeedLoadedFetcher,
        private readonly UpdateStatsLoaded\Handler $updateStatsLoaded,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('load-albums')
            ->setDescription('Load albums command')
            ->addArgument('mod', InputArgument::REQUIRED, 'mod?')
            ->addArgument('tokenId', InputArgument::OPTIONAL, 'token ID?');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Helper::bootDelay($output);

        $mod = (int)$input->getArgument('mod');

        $tokenId = null;
        if (null !== $input->getArgument('tokenId')) {
            $tokenId = (int)$input->getArgument('tokenId');
        }

        $output->writeln('<info>[MOD: ' . $mod . ', TOKEN: ' . ($tokenId ?: '-') . '] Loader albums started!</info>');

        $this->setTidalDLToken($tokenId);

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $albumArtist = $this->findNeedLoadedFetcher->fetch($mod);

            if (null === $albumArtist) {
                $output->writeln('<info>' . $this->time() . 'no albums (sleep...)</info>');
                sleep(5 * 60);
                continue;
            }

            $album = $this->albumRepository->getById($albumArtist->getAlbumId());
            $artist = $this->artistRepository->getById($albumArtist->getArtistId());

            $output->writeln('<info>' . $this->time() . '[albumId: ' . $albumArtist->getAlbumId() . '] ' . $album->getSpotifyName() . '</info>');

            try {
                $this->albumLoader->handle($artist, $album, $output);

                $albumArtist->setLoaded();
                $this->albumArtistRepository->add($albumArtist);

                $this->em->flush();

                $this->updateStatsLoaded->handle($artist->getId());
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

    private function setTidalDLToken(?int $tokenId): void
    {
        while (true) {
            if (null !== $tokenId) {
                $accessToken = $this->tidalTokenRepository->findById($tokenId);
            } else {
                $accessToken = $this->tidalTokenRepository->findFirstActive(TidalToken::TYPE_DL);
            }

            if (
                null === $accessToken ||
                $accessToken->getType() !== TidalToken::TYPE_DL ||
                !$accessToken->isActive()
            ) {
                echo PHP_EOL . $this->time() . 'NO ACCESS TOKENS!' . PHP_EOL;
                sleep(Constant::SLEEP_NO_ACCESS_TOKEN);

                continue;
            }

            $this->tidalDL->setAccessToken($accessToken->getAccessToken());
            return;
        }
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }
}
