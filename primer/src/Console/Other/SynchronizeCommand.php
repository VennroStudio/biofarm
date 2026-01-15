<?php

declare(strict_types=1);

namespace App\Console\Other;

use App\Components\OAuth\Generator\AccessToken;
use App\Components\RestServiceClient;
use App\Console\HelperData;
use App\Modules\Constant;
use App\Modules\Entity\Album\Album;
use App\Modules\Entity\AlbumArtist\AlbumArtist;
use App\Modules\Entity\AppleAlbum\AppleAlbumRepository;
use App\Modules\Entity\AppleTrack\AppleTrackRepository;
use App\Modules\Entity\Artist\Artist;
use App\Modules\Entity\TidalAlbum\TidalAlbumRepository;
use App\Modules\Entity\TidalTrack\TidalTrackRepository;
use App\Modules\Entity\Track\Track;
use App\Modules\Query\Artists\FindArtistNeedSynchronize;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function App\Components\env;

class SynchronizeCommand extends Command
{
    private string $host;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TidalAlbumRepository $tidalAlbumRepository,
        private readonly TidalTrackRepository $tidalTrackRepository,
        private readonly AppleAlbumRepository $appleAlbumRepository,
        private readonly AppleTrackRepository $appleTrackRepository,
        private readonly RestServiceClient $restServiceClient,
        private readonly FindArtistNeedSynchronize\Fetcher $findArtistNeedSynchronizeFetcher,
        private readonly HelperData $helperData,
    ) {
        parent::__construct();

        $this->host = env('HOST_API_LO');
    }

    protected function configure(): void
    {
        $this
            ->setName('synchronize')
            ->setDescription('Synchronize command')
            ->addArgument('mod', InputArgument::OPTIONAL, 'mod?');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mod = null;
        if (null !== $input->getArgument('mod')) {
            $mod = (int)$input->getArgument('mod');
        }

        $output->writeln('<info>Synchronize started!</info>');

        $timeStart = time();

        while (true) {
            if (!$this->em->isOpen()) {
                $output->writeln('<error>Connection closed!</error>');
                return 1;
            }

            $this->em->clear();

            $artist = $this->findArtistNeedSynchronizeFetcher->fetch($mod);

            if (null === $artist) {
                $output->writeln('<info>' . $this->time() . 'no artists (sleep...)</info>');
                sleep(60);
                continue;
            }

            $output->writeln('<info>' . $this->time() . '[artistId: ' . $artist->getId() . '] ' . $artist->getDescription() . '</info>');

            try {
                $this->synchronize($artist);

                $artist->setSynchronized();
                $this->em->flush();
            } catch (Throwable $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                sleep(60);
                continue;
            }

            if (time() - $timeStart > Constant::RELOAD_CONTAINER_INTERVAL) {
                break;
            }
        }

        return 0;
    }

    /** @throws Exception|GuzzleException */
    private function synchronize(Artist $artist): void
    {
        $accessToken = AccessToken::for(
            userId: (string)$artist->getUserId(),
            expires: new DateTimeImmutable('+5 hours')
        );

        foreach ($this->getAlbums($artist->getId()) as $album) {
            $this->synchronizeAlbum($accessToken, $album);
        }
    }

    /** @throws Exception|GuzzleException */
    private function synchronizeAlbum(string $accessToken, Album $album): void
    {
        if (null === $album->getLoAlbumId()) {
            return;
        }

        $tidalAlbumId = $album->getTidalAlbumId();
        if (null === $tidalAlbumId) {
            return;
        }

        $tidalAlbum = $this->tidalAlbumRepository->getById($tidalAlbumId);
        $appleAlbum = null !== $album->getAppleAlbumId() ? $this->appleAlbumRepository->findById($album->getAppleAlbumId()) : null;

        $body = [
            ...$this->helperData->album($album, $tidalAlbum, $appleAlbum, true),
        ];

        $response = $this->restServiceClient->put(
            url: $this->host . '/v1/audio-albums/' . $album->getLoAlbumId(),
            body: $body,
            accessToken: $accessToken
        );

        if (!isset($response['data']['success'])) {
            throw new Exception('Can not edit audio-album');
        }

        $this->synchronizeAudios($accessToken, $album->getId());
    }

    /** @throws Exception|GuzzleException */
    private function synchronizeAudios(string $accessToken, int $albumId): void
    {
        foreach ($this->getTracks($albumId) as $track) {
            if (null === $track->getLoTrackId()) {
                continue;
            }

            $tidalTrackId = $track->getTidalTrackId();
            if (null === $tidalTrackId) {
                return;
            }

            $tidalTrack = $this->tidalTrackRepository->getById($tidalTrackId);
            $appleTrack = null !== $track->getAppleTrackId() ? $this->appleTrackRepository->findById($track->getAppleTrackId()) : null;

            $body = [
                ...$this->helperData->audio($track, $tidalTrack, $appleTrack, true),
            ];

            $response = $this->restServiceClient->put(
                url: $this->host . '/v1/audios/' . $track->getLoTrackId(),
                body: $body,
                accessToken: $accessToken
            );

            if (!isset($response['data']['success'])) {
                throw new Exception('Can not edit audio');
            }
        }
    }

    /** @return Album[] */
    private function getAlbums(int $artistId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->from(Album::class, 'a')
            ->innerJoin(AlbumArtist::class, 'aa', Join::WITH, 'aa.albumId = a.id AND aa.artistId = :artistId')
            ->andWhere('a.loAlbumId IS NOT NULL')
            ->orderBy('a.spotifyReleasedAt', 'ASC')
            ->addOrderBy('a.id', 'ASC')
            ->setParameter('artistId', $artistId);

        /** @var Album[] $albums */
        $albums = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($albums as $album) {
            $items[] = $album;
        }

        return $items;
    }

    /** @return Track[] */
    private function getTracks(int $albumId): array
    {
        $queryBuilder = $this->em->createQueryBuilder();

        $queryBuilder
            ->select('t')
            ->from(Track::class, 't')
            ->andWhere('t.loTrackId IS NOT NULL')
            ->andWhere('t.albumId = :albumId')
            ->orderBy('t.id', 'ASC')
            ->setParameter('albumId', $albumId);

        /** @var Track[] $tracks */
        $tracks = $queryBuilder->getQuery()->getResult();

        $items = [];

        foreach ($tracks as $track) {
            $items[] = $track;
        }

        return $items;
    }

    private function time(): string
    {
        return '[' . date('d.m.Y H:i') . '] ';
    }
}
