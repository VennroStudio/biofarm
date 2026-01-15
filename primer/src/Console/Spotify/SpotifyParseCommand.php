<?php

declare(strict_types=1);

namespace App\Console\Spotify;

use App\Components\Helper;
use App\Modules\Constant;
use App\Modules\Query\Artists\FindArtistNeedSpotifyUpdate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SpotifyParseCommand extends Command
{
    private const INTERVAL = 5;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FindArtistNeedSpotifyUpdate\Fetcher $findArtistNeedSpotifyUpdateFetcher,
        private readonly SpotifyAlbumsParser $spotifyAlbumsParser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('spotify-parse')
            ->setDescription('Spotify parse command')
            ->addArgument('full', InputArgument::REQUIRED, 'full scan?')
            ->addArgument('mod', InputArgument::OPTIONAL, 'mod?')
            ->addArgument('tokenId', InputArgument::OPTIONAL, 'token ID?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Helper::bootDelay($output);

        $isFullScan = (int)$input->getArgument('full') === 1;

        $mod = null;
        if (null !== $input->getArgument('mod')) {
            $mod = (int)$input->getArgument('mod');
        }

        $tokenId = null;
        if (null !== $input->getArgument('tokenId')) {
            $tokenId = (int)$input->getArgument('tokenId');
        }

        $output->writeln('<info>[FULL SCAN: ' . ($isFullScan ? 'YES' : 'NO') . ', MOD: ' . (null !== $mod ? $mod : '-') . ', TOKEN: ' . ($tokenId ?: '-') . '] Parse Spotify started!</info>');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $artist = $this->findArtistNeedSpotifyUpdateFetcher->fetch($isFullScan, $mod);

            if (null === $artist) {
                $output->writeln('<info>' . $this->time() . 'no artists (sleep...)</info>');
                sleep(60);
                continue;
            }

            try {
                $output->writeln('<info>' . $this->time() . '[artistId: ' . $artist->getId() . '] ' . $artist->getDescription() . '</info>');
                $this->spotifyAlbumsParser->handle($artist->getId(), $isFullScan, $tokenId);
            } catch (Throwable $e) {
                $output->writeln('<error>' . $this->time() . $e->getMessage() . '</error>');
                sleep(10 * 60);
                continue;
            }

            // $output->writeln('<info>sleep ' . (self::INTERVAL / 60) . ' min...</info>');
            sleep(self::INTERVAL);

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL_10) {
                break;
            }
        }

        return 0;
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }
}
