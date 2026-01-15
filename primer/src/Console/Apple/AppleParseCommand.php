<?php

declare(strict_types=1);

namespace App\Console\Apple;

use App\Components\AppleGrab\RateLimitException;
use App\Components\Helper;
use App\Modules\Constant;
use App\Modules\Query\Artists\FindArtistNeedAppleUpdate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class AppleParseCommand extends Command
{
    // private const INTERVAL = 30;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FindArtistNeedAppleUpdate\Fetcher $findArtistNeedAppleUpdateFetcher,
        private readonly AppleAlbumsParser $appleAlbumsParser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('apple-parse')
            ->setDescription('Apple parse command')
            ->addArgument('full', InputArgument::REQUIRED, 'full scan?')
            ->addArgument('mod', InputArgument::OPTIONAL, 'mod?')
            ->addArgument('tokenId', InputArgument::OPTIONAL, 'token ID?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Helper::bootDelay($output);

        $excludedArtistIds = [];

        $isFullScan = (int)$input->getArgument('full') === 1;

        $mod = null;
        if (null !== $input->getArgument('mod')) {
            $mod = (int)$input->getArgument('mod');
        }

        $tokenId = null;
        if (null !== $input->getArgument('tokenId')) {
            $tokenId = (int)$input->getArgument('tokenId');
        }

        $output->writeln('[FULL SCAN: ' . ($isFullScan ? 'YES' : 'NO') . ', MOD: ' . (null !== $mod ? $mod : '-') . ', TOKEN: ' . ($tokenId ?: '-') . '] Parse Apple started!');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $artist = $this->findArtistNeedAppleUpdateFetcher->fetch($isFullScan, $excludedArtistIds, $mod);

            if (null === $artist) {
                $output->writeln($this->time() . 'no artists (sleep...)');
                sleep(60);
                continue;
            }

            try {
                $this->appleAlbumsParser->handle($artist, $isFullScan);

                $output->writeln($this->time() . '[id: ' . $artist->getId() . '] ' . $artist->getDescription());
            } catch (RateLimitException $e) {
                sleep(10 * 60);

                $output->writeln($this->time() . '[id: ' . $artist->getId() . '] ' . $artist->getDescription() . ' — ' . $e->getMessage());
                $excludedArtistIds[] = $artist->getId();

                continue;
            } catch (Throwable $e) {
                $output->writeln($this->time() . $e->getMessage());
                continue;
            }

            // $output->writeln('<info>sleep ' . (self::INTERVAL / 60) . ' min...</info>');
            // sleep(self::INTERVAL);

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL_4) {
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
