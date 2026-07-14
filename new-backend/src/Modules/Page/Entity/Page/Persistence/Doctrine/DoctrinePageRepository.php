<?php

declare(strict_types=1);

namespace App\Modules\Page\Entity\Page\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Page\Entity\Page\Page;
use App\Modules\Page\Entity\Page\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrinePageRepository implements PageRepository
{
    /** @var EntityRepository<Page> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $this->em->getRepository(Page::class);
    }

    #[Override]
    public function add(Page $page): void
    {
        $this->em->persist($page);
    }

    #[Override]
    public function getById(int $id): Page
    {
        return $this->findById($id) ?? throw new DomainExceptionModule('page', 'error.page_not_found', 1);
    }

    #[Override]
    public function findById(int $id): ?Page
    {
        return $this->repo->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    #[Override]
    public function findBySlugPath(string $slugPath): ?Page
    {
        return $this->repo->findOneBy(['slugPath' => $slugPath, 'deletedAt' => null]);
    }

    #[Override]
    public function findBySystemKey(string $systemKey): ?Page
    {
        return $this->repo->findOneBy(['systemKey' => $systemKey, 'deletedAt' => null]);
    }
}
