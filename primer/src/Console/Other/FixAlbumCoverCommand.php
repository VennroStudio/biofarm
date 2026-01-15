<?php

declare(strict_types=1);

namespace App\Console\Other;

use App\Components\OAuth\Generator\AccessToken;
use App\Console\Loader\AlbumCoverLoader;
use App\Modules\Entity\Album\AlbumRepository;
use App\Modules\Entity\AlbumArtist\AlbumArtistRepository;
use App\Modules\Entity\Artist\ArtistRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class FixAlbumCoverCommand extends Command
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly AlbumArtistRepository $albumArtistRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumCoverLoader $albumCoverLoader,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('other:fix-album-cover')
            ->setDescription('Fix album cover command');
    }

    /** @throws Throwable */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loAlbumIds = $this->getLOIds();

        while (\count($loAlbumIds) > 0) {
            try {
                foreach ($loAlbumIds as $k => $loAlbumId) {
                    $loAlbumId = (int)$loAlbumId;

                    $album = $this->albumRepository->findByLOId($loAlbumId);
                    if (null === $album) {
                        unset($loAlbumIds[$k]);
                        continue;
                    }

                    $albumArtist = $this->albumArtistRepository->findFirstByAlbumId($album->getId());
                    if (null === $albumArtist) {
                        unset($loAlbumIds[$k]);
                        continue;
                    }

                    $artist = $this->artistRepository->findById($albumArtist->getArtistId());
                    if (null === $artist) {
                        unset($loAlbumIds[$k]);
                        continue;
                    }

                    $accessToken = AccessToken::for((string)$artist->getUserId());
                    $this->albumCoverLoader->handle($artist->getUnionId(), $loAlbumId, $accessToken, $album->getSpotifyImages());

                    unset($loAlbumIds[$k]);
                    $output->writeln('<info>Cover loaded: ' . $loAlbumId . '</info>');
                }
            } catch (Throwable $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
            }
        }

        return 0;
    }

    /** @return string[] */
    private function getLOIds(): array
    {
        // SELECT GROUP_CONCAT(id) AS all_ids FROM audios_albums WHERE deleted_at IS NULL AND photo IS NULL AND upc IS NOT NULL ORDER BY audios_albums.released_at ASC;

        // $failed = '696966,720473,726571';

        $loAlbumIdsStr = '';

        return explode(',', $loAlbumIdsStr);
    }
}
