<?php

declare(strict_types=1);

namespace App\Modules\Entity\Track;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class TrackRepository
{
    /** @var EntityRepository<Track> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Track::class);
        $this->em = $em;
    }

    public function getCountMapped(): int
    {
        return $this->repo->count(['isApproved' => 1, 'isReissued' => 0]);
    }

    public function getCountNotLoaded(): int
    {
        $sql = 'SELECT COUNT(*) as count FROM tracks INNER JOIN albums ON albums.id = tracks.album_id WHERE lo_track_id IS NULL AND tracks.is_approved = 1 AND tracks.is_reissued = 0 AND albums.all_tracks_mapped = 1';

        /** @var array{count: int|null}|false $rows */
        $rows = $this->em->getConnection()
            ->executeQuery($sql)
            ->fetchAssociative();

        if ($rows === false) {
            return 0;
        }

        return (int)$rows['count'];
    }

    public function getCountByAlbumId(int $albumId): int
    {
        return (int)$this->repo->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.albumId = :albumId')
            ->andWhere('a.tidalTrackId IS NOT NULL')
            ->setParameter('albumId', $albumId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCountApprovedByAlbumId(int $albumId): int
    {
        return (int)$this->repo->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->where('a.albumId = :albumId')
            ->andWhere('a.isApproved = 1')
            ->andWhere('a.tidalTrackId IS NOT NULL')
            ->setParameter('albumId', $albumId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @throws Exception */
    public function getById(int $id): Track
    {
        if (!$track = $this->findById($id)) {
            throw new Exception('Track Not Found');
        }

        return $track;
    }

    public function findById(int $id): ?Track
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByLoAudioId(string $loAudioId): ?Track
    {
        return $this->repo->findOneBy(['loTrackId' => $loAudioId]);
    }

    public function findBySpotifyId(string $spotifyId): ?Track
    {
        return $this->repo->findOneBy(['spotifyId' => $spotifyId]);
    }

    public function findByAppleId(int $appleId): ?Track
    {
        return $this->repo->findOneBy(['appleTrackId' => $appleId]);
    }

    public function add(Track $track): void
    {
        $this->em->persist($track);
    }

    public function remove(Track $track): void
    {
        $this->em->remove($track);
    }
}
