<?php

declare(strict_types=1);

namespace App\Console\Refresh\Tracks;

use App\Components\Helper;
use App\Components\SpotifyGrab\SpotifyGrab;
use App\Modules\Constant;
use App\Modules\Entity\TrackAdditional\TrackAdditional;
use App\Modules\Entity\TrackAdditional\TrackAdditionalRepository;
use App\Modules\Query\FindTrackNeedSpotifyAdditionalUpdate;
use App\Modules\Query\GetSpotifyToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class SpotifyAdditionalParseCommand extends Command
{
    private const INTERVAL = 10;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FindTrackNeedSpotifyAdditionalUpdate\Fetcher $findTrackFetcher,
        private readonly SpotifyGrab $spotifyGrab,
        private readonly GetSpotifyToken\Fetcher $spotifyTokenFetcher,
        private readonly TrackAdditionalRepository $trackAdditionalRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('spotify-additional-parse')
            ->setDescription('Spotify additional parse command')
            ->addArgument('mod', InputArgument::OPTIONAL, 'mod?')
            ->addArgument('tokenId', InputArgument::OPTIONAL, 'token ID?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Helper::bootDelay($output);

        $mod = null;
        if (null !== $input->getArgument('mod')) {
            $mod = (int)$input->getArgument('mod');
        }

        $tokenId = null;
        if (null !== $input->getArgument('tokenId')) {
            $tokenId = (int)$input->getArgument('tokenId');
        }

        $output->writeln('<info>[MOD: ' . (null !== $mod ? $mod : '-') . ', TOKEN: ' . ($tokenId ?: '-') . '] Parse Spotify additional started!</info>');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $track = $this->findTrackFetcher->fetch($mod);

            if (null === $track) {
                $output->writeln('<info>' . $this->time() . 'no tracks (sleep...)</info>');
                sleep(60);
                continue;
            }

            $trackAdditional = $this->trackAdditionalRepository->findByTrackId($track->getId());
            if (null === $trackAdditional) {
                $trackAdditional = TrackAdditional::create($track->getId());
                $this->em->persist($trackAdditional);
            }

            $this->refreshAccessToken($tokenId);

            try {
                $output->writeln('<info>' . $this->time() . '[trackId: ' . $track->getId() . '] ' . $track->getSpotifyName() . '</info>');

                $features = $this->getFeatures($track->getSpotifyId());
                $trackAdditional->setSpotifyFeatures(
                    !empty($features) ? json_encode($features) : null
                );

                $analysis = $this->getAnalysis($track->getSpotifyId());

                /** @var array|null $meta */
                $meta = $analysis['meta'] ?? null;
                /** @var array|null $trackInfo */
                $trackInfo = $analysis['track'] ?? null;
                /** @var array|null $bars */
                $bars = $analysis['bars'] ?? null;
                /** @var array|null $beats */
                $beats = $analysis['beats'] ?? null;
                /** @var array|null $sections */
                $sections = $analysis['sections'] ?? null;
                // $segments = $analysis['segments'] ?? null;
                /** @var array|null $tatums */
                $tatums = $analysis['tatums'] ?? null;

                $trackAdditional->setSpotifyAnalysisMeta(
                    !empty($meta) ? json_encode($meta) : null
                );

                $trackAdditional->setSpotifyAnalysisTrack(
                    !empty($trackInfo) ? json_encode($trackInfo) : null
                );

                $trackAdditional->setSpotifyAnalysisBars(
                    !empty($bars) ? json_encode($bars) : null
                );

                $trackAdditional->setSpotifyAnalysisBeats(
                    !empty($beats) ? json_encode($beats) : null
                );

                $trackAdditional->setSpotifyAnalysisSections(
                    !empty($sections) ? json_encode($sections) : null
                );

                // $trackAdditional->setSpotifyAnalysisSegments(
                //    !empty($segments) ? json_encode($segments) : null
                // );

                $trackAdditional->setSpotifyAnalysisTatums(
                    !empty($tatums) ? json_encode($tatums) : null
                );

                $track->setSpotifyAdditionalChecked();
                $this->em->flush();
            } catch (Throwable $e) {
                $output->writeln('<error>' . $this->time() . $e->getMessage() . '</error>');
                sleep(5 * 60);
                continue;
            }

            // $output->writeln('<info>sleep ' . self::INTERVAL . ' sec...</info>');
            sleep(self::INTERVAL);

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL) {
                break;
            }
        }

        return 0;
    }

    private function getFeatures(string $trackId): ?array
    {
        return $this->spotifyGrab->getAudioFeatures($trackId);
    }

    private function getAnalysis(string $trackId): ?array
    {
        return $this->spotifyGrab->getAudioAnalysis($trackId);
    }

    private function refreshAccessToken(?int $tokenId): void
    {
        $accessToken = $this->getAccessToken($tokenId);
        $this->spotifyGrab->setAccessToken($accessToken);
    }

    private function getAccessToken(?int $tokenId): string
    {
        while (true) {
            $accessToken = $this->spotifyTokenFetcher->fetch($tokenId);

            if (null !== $accessToken) {
                return $accessToken;
            }

            echo PHP_EOL . 'NO ACCESS TOKENS!' . PHP_EOL;
            sleep(Constant::SLEEP_NO_ACCESS_TOKEN);
        }
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }
}
