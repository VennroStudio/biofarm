<?php

declare(strict_types=1);

namespace App\Console\TrackProblematic;

use App\Modules\Command\Artist\ResetChecking\Command as ResetCommand;
use App\Modules\Command\Artist\ResetChecking\Handler as ResetHandler;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class TrackProblematicResetCommand extends Command
{
    private const MAX_ARTISTS = 200;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ResetHandler $resetHandler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('process-problematic-reset')
            ->setDescription('Reset all artists from track_problematic table');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $artistIds = $this->getUniqueArtistIds();

        if (empty($artistIds)) {
            $output->writeln('Артисты не найдены');
            return Command::SUCCESS;
        }

        $totalFound = \count($artistIds);
        $output->writeln("Найдено {$totalFound} уникальных артистов (обработка максимум " . self::MAX_ARTISTS . ')');

        $successCount = 0;
        $errorCount = 0;

        foreach ($artistIds as $artistId) {
            if ($successCount >= self::MAX_ARTISTS) {
                $output->writeln('');
                $output->writeln('Достигнут максимальный лимит ' . self::MAX_ARTISTS . ' артистов');
                break;
            }

            try {
                $this->resetArtist($artistId);
                $this->updateTracksStatus($artistId);

                $output->writeln("Отправлен на переобход artist_id={$artistId}");
                ++$successCount;

                sleep(1);
            } catch (Throwable $e) {
                $output->writeln("Ошибка artist_id={$artistId}: " . $e->getMessage());
                ++$errorCount;
            }
        }

        $output->writeln("Команда завершена! Успешно: {$successCount}, Ошибок: {$errorCount}");

        return Command::SUCCESS;
    }

    /**
     * @return int[]
     * @throws Exception
     */
    private function getUniqueArtistIds(): array
    {
        $sql = 'SELECT DISTINCT artist_id FROM track_problematic WHERE artist_id IS NOT NULL AND status = 5 AND artist_id != 0 ORDER BY artist_id LIMIT ' . self::MAX_ARTISTS;

        $connection = $this->em->getConnection();
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        return array_map('intval', $result->fetchFirstColumn());
    }

    /**
     * @throws Throwable
     */
    private function resetArtist(int $artistId): void
    {
        $command = new ResetCommand(artistId: $artistId);
        $this->resetHandler->handle($command);
    }

    /**
     * @throws Exception
     */
    private function updateTracksStatus(int $artistId): void
    {
        $sql = 'UPDATE track_problematic SET status = 0 WHERE artist_id = :artist_id';

        $connection = $this->em->getConnection();
        $connection->executeStatement($sql, [
            'artist_id' => $artistId,
        ]);
    }
}
