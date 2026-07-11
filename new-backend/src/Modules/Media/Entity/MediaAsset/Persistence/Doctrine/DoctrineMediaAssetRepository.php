<?php

declare(strict_types=1);

namespace App\Modules\Media\Entity\MediaAsset\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Media\Entity\MediaAsset\MediaAsset;
use App\Modules\Media\Entity\MediaAsset\MediaAssetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineMediaAssetRepository implements MediaAssetRepository
{
    /** @var EntityRepository<MediaAsset> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $em->getRepository(MediaAsset::class);
    }

    #[Override]
    public function add(MediaAsset $asset): void
    {
        $this->em->persist($asset);
    }

    #[Override]
    public function remove(MediaAsset $asset): void
    {
        $this->em->remove($asset);
    }

    #[Override]
    public function getById(int $id): MediaAsset
    {
        if (!$asset = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: 'media',
                message: 'error.media_asset_not_found',
                code: 1,
            );
        }

        return $asset;
    }

    #[Override]
    public function findById(int $id): ?MediaAsset
    {
        return $this->repo->findOneBy(['id' => $id]);
    }
}
