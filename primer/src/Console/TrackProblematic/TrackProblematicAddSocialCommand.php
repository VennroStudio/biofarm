<?php

declare(strict_types=1);

namespace App\Console\TrackProblematic;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TrackProblematicAddSocialCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('process-problematic-tidal')
            ->setDescription('Process problematic tracks with Tidal URLs');
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $artists = $this->getArtistsWithTidalUrls();

        $output->writeln('Найдено ' . \count($artists) . ' артистов');

        foreach ($artists as $artist) {
            $artistId = $artist['artist_id'];
            $tidalUrl = $this->addListenSubdomain($artist['tidal_url']);

            if ($this->artistSocialExists($artistId, $tidalUrl)) {
                continue;
            }

            $this->addArtistSocial($artistId, $tidalUrl);
            $output->writeln("Добавлено: artist_id={$artistId} url={$tidalUrl}");
        }

        $output->writeln('Обработка завершена!');

        return Command::SUCCESS;
    }

    /**
     * @return list<array{artist_id: int, tidal_url: string}>
     * @throws Exception
     */
    private function getArtistsWithTidalUrls(): array
    {
        $sql = 'SELECT artist_id, tidal_url FROM track_problematic WHERE tidal_url IS NOT NULL AND tidal_url != :empty AND artist_id != 0';

        $connection = $this->em->getConnection();
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['empty' => '']);

        /** @var list<array{artist_id: int, tidal_url: string}> */
        return $result->fetchAllAssociative();
    }

    private function addListenSubdomain(string $url): string
    {
        return str_replace('https://', 'https://listen.', $url);
    }

    /**
     * @throws Exception
     */
    private function artistSocialExists(int $artistId, string $tidalUrl): bool
    {
        $sql = 'SELECT id FROM artist_socials WHERE artist_id = :artist_id AND type = 0 AND url = :url LIMIT 1';
        $connection = $this->em->getConnection();
        $result = $connection->executeQuery($sql, [
            'artist_id' => $artistId,
            'url'       => $tidalUrl,
        ]);

        /** @var array{id: int}|false $row */
        $row = $result->fetchAssociative();

        return $row !== false;
    }

    /**
     * @throws Exception
     */
    private function addArtistSocial(int $artistId, string $tidalUrl): void
    {
        $sql = 'INSERT INTO artist_socials (artist_id, type, url, description, created_at) VALUES (:artist_id, 0, :url, "problematic", :created_at)';
        $connection = $this->em->getConnection();
        $connection->executeStatement($sql, [
            'artist_id'  => $artistId,
            'url'        => $tidalUrl,
            'created_at' => time(),
        ]);
    }
}
