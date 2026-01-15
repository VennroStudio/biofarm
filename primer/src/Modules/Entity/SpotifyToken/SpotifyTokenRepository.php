<?php

declare(strict_types=1);

namespace App\Modules\Entity\SpotifyToken;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class SpotifyTokenRepository
{
    /** @var EntityRepository<SpotifyToken> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(SpotifyToken::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): SpotifyToken
    {
        if (!$spotifyToken = $this->findById($id)) {
            throw new Exception('SpotifyToken Not Found');
        }

        return $spotifyToken;
    }

    public function findById(int $id): ?SpotifyToken
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByAccessToken(string $accessToken): ?SpotifyToken
    {
        return $this->repo->findOneBy(['accessToken' => $accessToken]);
    }

    public function add(SpotifyToken $spotifyToken): void
    {
        $this->em->persist($spotifyToken);
    }

    public function remove(SpotifyToken $spotifyToken): void
    {
        $this->em->remove($spotifyToken);
    }
}
