<?php

declare(strict_types=1);

namespace App\Console\Playlists;

use App\Components\Helper;
use App\Modules\Constant;
use App\Modules\Query\Playlists\FindPlaylistNeedUpdate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class PlaylistsCommand extends Command
{
    private const INTERVAL = 20;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FindPlaylistNeedUpdate\Fetcher $findPlaylistNeedUpdate,
        private readonly PlaylistsAppleParser $playlistsParser,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('playlists')
            ->setDescription('Playlists command');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Helper::bootDelay($output);

        $output->writeln('<info>Playlists started!</info>');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $playlist = $this->findPlaylistNeedUpdate->fetch();

            if (null === $playlist) {
                $output->writeln('<info>' . $this->time() . 'no playlist (sleep...)</info>');
                sleep(5 * 60);
                continue;
            }

            $output->writeln('<info>' . $this->time() . '[playlistId: ' . $playlist->getId() . '] ' . $playlist->getName() . '</info>');

            try {
                $this->playlistsParser->handle($playlist);
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
