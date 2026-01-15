<?php

declare(strict_types=1);

namespace App\Console\TrackProblematic;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TrackProblematicDeleteStatusCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('track-problematic-delete-status-5')
            ->setDescription('Delete all records with status 5 from track_problematic');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sql = 'DELETE FROM track_problematic WHERE status = 5 AND artist_id != 0';
        $connection = $this->em->getConnection();
        $count = $connection->executeStatement($sql);

        $output->writeln('Удалено ' . $count . ' треков со status=5');

        return Command::SUCCESS;
    }
}
