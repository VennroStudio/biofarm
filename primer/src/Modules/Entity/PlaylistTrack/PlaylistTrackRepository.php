<?php

declare(strict_types=1);

namespace App\Modules\Entity\PlaylistTrack;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class PlaylistTrackRepository
{
    /**
     * @var EntityRepository<PlaylistTrack>
     */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(PlaylistTrack::class);
        $this->em = $em;
    }

    public function findByPlaylistAndTrack(int $playlistId, int $trackId): ?PlaylistTrack
    {
        return $this->repo->findOneBy([
            'playlistId' => $playlistId,
            'trackId' => $trackId,
        ]);
    }

    public function add(PlaylistTrack $playlistTrack): void
    {
        $this->em->persist($playlistTrack);
    }

    public function remove(PlaylistTrack $playlistTrack): void
    {
        $this->em->remove($playlistTrack);
    }

    public function removeAllByPlaylistId(int $playlistId): void
    {
        $this->em->createQueryBuilder()
            ->delete(PlaylistTrack::class, 'pt')
            ->andWhere('pt.playlistId = :playlistId')
            ->setParameter('playlistId', $playlistId)
            ->getQuery()
            ->execute();
    }
}
