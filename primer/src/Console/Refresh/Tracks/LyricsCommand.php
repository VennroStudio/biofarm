<?php

declare(strict_types=1);

namespace App\Console\Refresh\Tracks;

use App\Components\Helper;
use App\Components\NeteaseGrab\NeteaseGrab;
use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Modules\Constant;
use App\Modules\Entity\Album\AlbumRepository;
use App\Modules\Entity\Track\Track;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function App\Components\env;

class LyricsCommand extends Command
{
    private const INTERVAL = 5 * 60;
    private string $host;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RestServiceClient $restServiceClient,
        private readonly NeteaseGrab $neteaseGrab,
        private readonly AlbumRepository $albumRepository,
    ) {
        parent::__construct();

        $this->host = env('HOST_API_LO');
    }

    protected function configure(): void
    {
        $this
            ->setName('refresh:lyrics')
            ->setDescription('Refresh tracks lyrics command');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Helper::bootDelay($output);

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $tracks = $this->getTracks();

            if (\count($tracks) === 0) {
                sleep(self::INTERVAL);
                continue;
            }

            foreach ($tracks as $track) {
                if (!$this->refreshLyrics($track)) {
                    continue;
                }

                $output->writeln('<info>[' . $track->getId() . '] ' . $track->getSpotifyName() . '</info>');
            }

            sleep(self::INTERVAL);

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL) {
                break;
            }
        }

        return 0;
    }

    /** @throws Exception|GuzzleException */
    private function refreshLyrics(Track $track): bool
    {
        $loTrackId = $track->getLoTrackId();

        if (null === $loTrackId) {
            return false;
        }

        $album = $this->albumRepository->getById($track->getAlbumId());
        $artists = explode(', ', $track->getSeparateArtists());

        $lyrics = null;

        foreach ($artists as $artistName) {
            $lyrics = $this->neteaseGrab->searchLyric($track->getSpotifyName(), $artistName, $album->getSpotifyName());

            if (null !== $lyrics) {
                break;
            }
        }

        if (null !== $lyrics) {
            $track->setLyricsNetease($lyrics);
            $this->updateLyric($loTrackId, $lyrics);
        }

        $track->setLyricsUpdatedAt(time());
        $this->em->flush();

        return null !== $lyrics;
    }

    /** @return Track[] */
    private function getTracks(): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from(Track::class, 't')
            ->andWhere('t.loTrackId IS NOT NULL')
            ->andWhere('t.lyrics IS NULL AND t.lyricsNetease IS NULL')
            ->andWhere('t.uploadedAt IS NULL OR t.uploadedAt < :uploadedAt')
            ->andWhere('t.lyricsUpdatedAt IS NULL OR t.lyricsUpdatedAt < :lyricsUpdatedAt')
            ->orderBy('t.lyricsUpdatedAt', 'ASC')
            ->addOrderBy('t.id', 'ASC')
            ->setParameter('uploadedAt', time() - 10 * 60)
            ->setParameter('lyricsUpdatedAt', time() - Constant::LYRICS_INTERVAL_CHECKING)
            ->setMaxResults(50);

        /** @var Track[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($tracks as $track) {
            $items[] = $track;
        }

        return $items;
    }

    /** @throws Exception|GuzzleException */
    private function updateLyric(int $audioId, string $lyrics): void
    {
        $accessToken = AccessToken::for('5');

        $response = $this->restServiceClient->post(
            url: $this->host . '/v1/audios/' . $audioId . '/lyrics',
            body: [
                'lyrics' => $lyrics,
            ],
            accessToken: $accessToken
        );

        if (!isset($response['data']['success'])) {
            echo PHP_EOL . 'DONT SAVE LYRICS';
        }
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }
}
