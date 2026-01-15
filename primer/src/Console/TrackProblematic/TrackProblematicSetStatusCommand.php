<?php

declare(strict_types=1);

namespace App\Console\TrackProblematic;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TrackProblematicSetStatusCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('track-problematic-set-status-5')
            ->setDescription('Set status 5 for all records in track_problematic');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = 'UPDATE track_problematic SET status = 5 WHERE artist_id != 0';
        $connection = $this->em->getConnection();
        $count = $connection->executeStatement($sql);

        $output->writeln('Установлен status=5 для ' . $count . ' треков');

        return Command::SUCCESS;
    }
}
