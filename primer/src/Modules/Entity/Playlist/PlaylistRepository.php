<?php

declare(strict_types=1);

namespace App\Modules\Entity\Playlist;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class PlaylistRepository
{
    /** @var EntityRepository<Playlist> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Playlist::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): Playlist
    {
        if (!$playlist = $this->findById($id)) {
            throw new Exception('Playlist Not Found');
        }

        return $playlist;
    }

    public function findById(int $id): ?Playlist
    {
        return $this->repo->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    public function findByUrl(string $url): ?Playlist
    {
        return $this->repo->findOneBy(['url' => $url, 'deletedAt' => null]);
    }

    public function add(Playlist $playlist): void
    {
        $this->em->persist($playlist);
    }

    public function remove(Playlist $playlist): void
    {
        $this->em->remove($playlist);
    }
}
