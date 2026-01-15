<?php

declare(strict_types=1);

namespace App\Modules\Entity\Settings;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class SettingsRepository
{
    /** @var EntityRepository<Settings> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(Settings::class);
        $this->em = $em;
    }

    /** @throws Exception */
    public function getByKey(string $key): Settings
    {
        if (!$setting = $this->findByKey($key)) {
            throw new Exception('Setting Not Found');
        }

        return $setting;
    }

    public function findByKey(string $key): ?Settings
    {
        return $this->repo->findOneBy(['key' => $key]);
    }

    /** @return Settings[] */
    public function findAll(): array
    {
        return $this->repo->findAll();
    }

    public function add(Settings $setting): void
    {
        $this->em->persist($setting);
    }

    public function remove(Settings $setting): void
    {
        $this->em->remove($setting);
    }
}
