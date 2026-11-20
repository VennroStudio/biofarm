<?php

declare(strict_types=1);

namespace App\Modules\Setting\Entity\SiteSetting\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Setting\Entity\SiteSetting\SiteSetting;
use App\Modules\Setting\Entity\SiteSetting\SiteSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineSiteSettingRepository implements SiteSettingRepository
{
    /** @var EntityRepository<SiteSetting> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $em->getRepository(SiteSetting::class);
    }

    #[Override]
    public function add(SiteSetting $setting): void
    {
        $this->em->persist($setting);
    }

    #[Override]
    public function getByKey(string $key): SiteSetting
    {
        if (!$setting = $this->findByKey($key)) {
            throw new DomainExceptionModule(
                module: 'setting',
                message: 'error.site_setting_not_found',
                code: 1,
            );
        }

        return $setting;
    }

    #[Override]
    public function findByKey(string $key): ?SiteSetting
    {
        return $this->repo->findOneBy(['key' => $key]);
    }
}
