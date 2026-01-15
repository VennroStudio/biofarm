<?php

declare(strict_types=1);

namespace App\Modules\Entity\PlaylistTranslate;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

class PlaylistTranslateRepository
{
    /** @var EntityRepository<PlaylistTranslate> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(PlaylistTranslate::class);
        $this->em = $em;
    }

    /** @throws Exception */
    public function getById(int $id): PlaylistTranslate
    {
        if (!$translate = $this->findById($id)) {
            throw new Exception('PlaylistTranslate Not Found');
        }

        return $translate;
    }

    public function findById(int $id): ?PlaylistTranslate
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findByLang(int $playlistId, string $lang): ?PlaylistTranslate
    {
        return $this->repo->findOneBy(['playlistId' => $playlistId, 'lang' => $lang]);
    }

    public function getCountWithoutPhoto(int $playlistId): int
    {
        return $this->repo->count(['playlistId' => $playlistId, 'photoHost' => null]);
    }

    public function add(PlaylistTranslate $translate): void
    {
        $this->em->persist($translate);
    }

    public function remove(PlaylistTranslate $translate): void
    {
        $this->em->remove($translate);
    }
}
