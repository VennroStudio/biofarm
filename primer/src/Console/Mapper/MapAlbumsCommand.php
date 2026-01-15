<?php

declare(strict_types=1);

namespace App\Console\Mapper;

use App\Components\Helper;
use App\Modules\Command\Artist\UpdateStatsMapped;
use App\Modules\Constant;
use App\Modules\Query\Artists\FindArtistNeedMap;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class MapAlbumsCommand extends Command
{
    private const INTERVAL = 20;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AlbumsMapper $albumsMapper,
        private readonly FindArtistNeedMap\Fetcher $findArtistNeedMapFetcher,
        private readonly UpdateStatsMapped\Handler $updateStatsMapped,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('map-albums')
            ->setDescription('Map albums command')
            ->addArgument('full', InputArgument::REQUIRED, 'full scan?')
            ->addArgument('mod', InputArgument::OPTIONAL, 'mod?');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Helper::bootDelay($output);

        $isFullScan = (int)$input->getArgument('full') === 1;

        $mod = null;
        $collectionNumber = null;

        if (null !== $input->getArgument('mod')) {
            $mod = (int)$input->getArgument('mod');
            $collectionNumber = $mod;

            if ($isFullScan) {
                $collectionNumber += 1000;
            }
        }

        $output->writeln('<info>[Number: ' . ($collectionNumber ?? '-') . '] Map albums started!</info>');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $artist = $this->findArtistNeedMapFetcher->fetch($isFullScan, $mod);

            if (null === $artist) {
                $output->writeln('<info>' . $this->time() . 'no artist (sleep...)</info>');
                sleep(5 * 60);
                continue;
            }

            $output->writeln('<info>' . $this->time() . '[artistId: ' . $artist->getId() . '] ' . $artist->getDescription() . '</info>');

            try {
                $this->albumsMapper->handle($artist, $collectionNumber);

                $this->updateStatsMapped->handle($artist->getId());
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

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }
}
