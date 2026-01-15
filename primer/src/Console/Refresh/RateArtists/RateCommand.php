<?php

declare(strict_types=1);

namespace App\Console\Refresh\RateArtists;

use App\Components\RestServiceClient;
use App\Modules\Constant;
use App\Modules\Entity\ArtistSocial\ArtistSocial;
use App\Modules\Query\Artists\FindArtistNeedSimilarUpdate;
use App\Modules\Query\Artists\GetArtistIdsBySpotifyIds;
use App\Modules\Query\Artists\GetArtistIdsByTidalIds;
use App\Modules\Query\Artists\GetArtistSocials;
use App\Modules\Query\Artists\GetUnionIdsByArtistIds;
use App\Modules\Query\Tracks\GetAudioIdsByTrackIds;
use App\Modules\Query\Tracks\GetTrackIdsBySpotifyIds;
use App\Modules\Query\Tracks\GetTrackIdsByTidalIds;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function App\Components\env;

class RateCommand extends Command
{
    private string $host;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FindArtistNeedSimilarUpdate\Fetcher $findArtistFetcher,
        private readonly GetArtistSocials\Fetcher $socialsFetcher,
        private readonly GetArtistIdsBySpotifyIds\Fetcher $artistIdsBySpotifyIdsFetcher,
        private readonly GetArtistIdsByTidalIds\Fetcher $artistIdsByTidalIdsFetcher,
        private readonly GetTrackIdsBySpotifyIds\Fetcher $trackIdsBySpotifyIdsFetcher,
        private readonly GetTrackIdsByTidalIds\Fetcher $trackIdsByTidalIdsFetcher,
        private readonly GetUnionIdsByArtistIds\Fetcher $unionIdsByArtistIdsFetcher,
        private readonly GetAudioIdsByTrackIds\Fetcher $audioIdsByTrackIdsFetcher,
        private readonly RestServiceClient $restServiceClient,
    ) {
        parent::__construct();

        $this->host = env('HOST_API_LO');
    }

    protected function configure(): void
    {
        $this
            ->setName('rate-artists')
            ->setDescription('Rate artists command')
            ->addArgument('mod', InputArgument::OPTIONAL, 'mod?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Rate artists started!</info>');

        $mod = null;
        if (null !== $input->getArgument('mod')) {
            $mod = (int)$input->getArgument('mod');
        }

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $artist = $this->findArtistFetcher->fetch($mod);

            if (null === $artist) {
                $output->writeln('<info>' . $this->time() . 'no artists (sleep...)</info>');
                sleep(60);
                continue;
            }

            try {
                $output->writeln('<info>' . $this->time() . '[artistId: ' . $artist->getId() . '] ' . $artist->getDescription() . '</info>');

                $similarArtistIds = $this->getSimilarArtists($artist->getId());
                $popularTrackIds = $this->getPopularTracks($artist->getId());
                $genres = $this->getGenres($artist->getId());

                $artist->setSimilarIds(!empty($similarArtistIds) ? json_encode($similarArtistIds) : null);
                $artist->setPopularTrackIds(!empty($popularTrackIds) ? json_encode($popularTrackIds) : null);

                $artist->setRateChecked();

                $this->em->flush();

                $this->sendToLO($artist->getUnionId(), $similarArtistIds, $popularTrackIds, $genres);
            } catch (Throwable $e) {
                $output->writeln('<error>' . $this->time() . $e->getMessage() . '</error>');
                sleep(5 * 60);
                continue;
            }

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL) {
                break;
            }
        }

        return 0;
    }

    /**
     * @return array{priority: int[], other: int[]}|array{}
     * @throws Exception
     */
    private function getSimilarArtists(int $artistId): array
    {
        /** @var int[] $priorityIds */
        $priorityIds = [];
        /** @var int[] $otherIds */
        $otherIds = [];

        $socials = $this->socialsFetcher->fetch(
            new GetArtistSocials\Query($artistId)
        );

        foreach ($socials as $social) {
            if (null === $social->getSimilarIds()) {
                continue;
            }

            /** @var string[] $similarIds */
            $similarIds = json_decode($social->getSimilarIds(), true);

            if ($social->getType() === ArtistSocial::TYPE_SPOTIFY) {
                $idsBySocial = $this->artistIdsBySpotifyIdsFetcher->fetch(
                    new GetArtistIdsBySpotifyIds\Query($similarIds)
                );
            } elseif ($social->getType() === ArtistSocial::TYPE_TIDAL) {
                $idsBySocial = $this->artistIdsByTidalIdsFetcher->fetch(
                    new GetArtistIdsByTidalIds\Query($similarIds)
                );
            } else {
                continue;
            }

            foreach ($idsBySocial as $id) {
                if (\in_array($id, $priorityIds, true)) {
                    continue;
                }

                if (\in_array($id, $otherIds, true)) {
                    $priorityIds[] = $id;

                    $key = array_search($id, $otherIds, true);
                    if ($key !== false) {
                        unset($otherIds[$key]);
                    }

                    continue;
                }

                $otherIds[] = $id;
            }
        }

        $priorityIds = array_unique($priorityIds);
        $otherIds = array_unique($otherIds);

        if (\count($priorityIds) === 0 && \count($otherIds) === 0) {
            return [];
        }

        return [
            'priority'  => $priorityIds,
            'other'     => $otherIds,
        ];
    }

    /**
     * @return array{priority: int[], other: int[]}|array{}
     * @throws Exception
     */
    private function getPopularTracks(int $artistId): array
    {
        /** @var int[] $priorityIds */
        $priorityIds = [];
        /** @var int[] $otherIds */
        $otherIds = [];

        $socials = $this->socialsFetcher->fetch(
            new GetArtistSocials\Query($artistId)
        );

        foreach ($socials as $social) {
            if (null === $social->getPopularTrackIds()) {
                continue;
            }

            /** @var string[] $popularIds */
            $popularIds = json_decode($social->getPopularTrackIds(), true);

            if ($social->getType() === ArtistSocial::TYPE_SPOTIFY) {
                $idsBySocial = $this->trackIdsBySpotifyIdsFetcher->fetch(
                    new GetTrackIdsBySpotifyIds\Query($popularIds)
                );
            } elseif ($social->getType() === ArtistSocial::TYPE_TIDAL) {
                $idsBySocial = $this->trackIdsByTidalIdsFetcher->fetch(
                    new GetTrackIdsByTidalIds\Query($popularIds)
                );
            } else {
                continue;
            }

            foreach ($idsBySocial as $id) {
                if ($social->getType() === ArtistSocial::TYPE_SPOTIFY) {
                    $priorityIds[] = $id;
                } else {
                    $otherIds[] = $id;
                }
            }
        }

        $priorityIds = array_unique($priorityIds);
        $otherIds = array_unique($otherIds);

        if (\count($priorityIds) === 0 && \count($otherIds) === 0) {
            return [];
        }

        return [
            'priority'  => $priorityIds,
            'other'     => $otherIds,
        ];
    }

    private function getGenres(int $artistId): array
    {
        /** @var string[] $genres */
        $genres = [];

        $socials = $this->socialsFetcher->fetch(
            new GetArtistSocials\Query($artistId, ArtistSocial::TYPE_APPLE)
        );

        foreach ($socials as $social) {
            $genres = array_merge($genres, $social->getAppleGenres());
        }

        return array_unique($genres);
    }

    /**
     * @param array{priority: int[], other: int[]}|array{} $similarArtistIds
     * @param array{priority: int[], other: int[]}|array{} $popularTrackIds
     * @throws Exception|GuzzleException
     */
    private function sendToLO(int $unionId, array $similarArtistIds, array $popularTrackIds, array $genres): void
    {
        $similarUnionIdsPriority = [];
        $similarUnionIdsOther = [];

        if (isset($similarArtistIds['priority'])) {
            $similarUnionIdsPriority = $this->unionIdsByArtistIdsFetcher->fetch(
                new GetUnionIdsByArtistIds\Query($similarArtistIds['priority'])
            );
        }

        if (isset($similarArtistIds['other'])) {
            $similarUnionIdsOther = $this->unionIdsByArtistIdsFetcher->fetch(
                new GetUnionIdsByArtistIds\Query($similarArtistIds['other'])
            );
        }

        $similarUnionIds = [
            'priority' => $similarUnionIdsPriority,
            'other' => $similarUnionIdsOther,
        ];

        $popularAudioIdsPriority = [];
        $popularAudioIdsOther = [];

        if (isset($popularTrackIds['priority'])) {
            $popularAudioIdsPriority = $this->audioIdsByTrackIdsFetcher->fetch(
                new GetAudioIdsByTrackIds\Query($popularTrackIds['priority'])
            );
        }

        if (isset($popularTrackIds['other'])) {
            $popularAudioIdsOther = $this->audioIdsByTrackIdsFetcher->fetch(
                new GetAudioIdsByTrackIds\Query($popularTrackIds['other'])
            );
        }

        $popularAudioIds = [
            'priority' => $popularAudioIdsPriority,
            'other' => $popularAudioIdsOther,
        ];

        $response = $this->restServiceClient->post(
            url: $this->host . '/v1/audios/refresh-artists',
            body: [
                'unionId' => $unionId,
                'similarUnionIds' => $similarUnionIds,
                'popularAudioIds' => $popularAudioIds,
                'genres' => $genres,
            ]
        );

        if (!isset($response['data']['success'])) {
            throw new Exception('Can not refresh artists data.');
        }

        // echo PHP_EOL;
        // echo 'similar: ' . json_encode($similarUnionIds) . PHP_EOL . PHP_EOL;
        // echo 'popular: ' . json_encode($popularAudioIds) . PHP_EOL . PHP_EOL;
        // echo 'genres: ' . json_encode($genres) . PHP_EOL;
        // exit('END');
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }
}
