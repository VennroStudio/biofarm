<?php

declare(strict_types=1);

namespace App\Modules\Entity\Album;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class AlbumRepository
{
    /** @var EntityRepository<Album> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Album::class);
        $this->em = $em;
    }

    public function getCountMapped(): int
    {
        return $this->repo->count(['isApproved' => 1, 'allTracksMapped' => 1, 'isReissued' => 0]);
    }

    public function getCountNotLoaded(): int
    {
        return $this->repo->count(['isApproved' => 1, 'allTracksMapped' => 1, 'isReissued' => 0, 'loAlbumId' => null]);
    }

    /** @throws Exception */
    public function getById(int $id): Album
    {
        if (!$album = $this->findById($id)) {
            throw new Exception('Album Not Found');
        }

        return $album;
    }

    public function findById(int $id): ?Album
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByLOId(int $id): ?Album
    {
        return $this->repo->findOneBy(['loAlbumId' => $id]);
    }

    public function findBySpotifyId(string $id): ?Album
    {
        return $this->repo->findOneBy(['spotifyId' => $id]);
    }

    public function add(Album $album): void
    {
        $this->em->persist($album);
    }

    public function resetAllNotApproved(int $artistId): void
    {
        $sql = '
            UPDATE albums AS a
            INNER JOIN album_artists AS aa ON a.id = aa.album_id
            SET a.tidal_album_id = NULL
            WHERE aa.artist_id = :artistId AND a.is_approved = 0
        ';
        $this->em->getConnection()->executeQuery($sql, ['artistId' => $artistId]);
    }

    public function remove(Album $album): void
    {
        $this->em->remove($album);
    }
}
