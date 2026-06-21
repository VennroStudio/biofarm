<?php

declare(strict_types=1);

namespace App\Modules\Blog\Entity\BlogPost\Persistence\Doctrine;

use App\Components\Exception\DomainExceptionModule;
use App\Modules\Blog\Entity\BlogPost\BlogPost;
use App\Modules\Blog\Entity\BlogPost\BlogPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;

final readonly class DoctrineBlogPostRepository implements BlogPostRepository
{
    /** @var EntityRepository<BlogPost> */
    private EntityRepository $repo;

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->repo = $this->em->getRepository(BlogPost::class);
    }

    #[Override]
    public function add(BlogPost $post): void
    {
        $this->em->persist($post);
    }

    #[Override]
    public function remove(BlogPost $post): void
    {
        $this->em->remove($post);
    }

    #[Override]
    public function getById(int $id): BlogPost
    {
        if (!$post = $this->findById($id)) {
            throw new DomainExceptionModule(
                module: 'blog',
                message: 'error.blog_post_not_found',
                code: 1,
            );
        }

        return $post;
    }

    #[Override]
    public function findById(int $id): ?BlogPost
    {
        return $this->repo->findOneBy(['id' => $id, 'deletedAt' => null]);
    }

    #[Override]
    public function findBySlug(string $slug): ?BlogPost
    {
        return $this->repo->findOneBy(['slug' => $slug, 'deletedAt' => null]);
    }
}
