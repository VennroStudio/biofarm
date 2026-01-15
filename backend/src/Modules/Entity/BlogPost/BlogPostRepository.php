<?php

declare(strict_types=1);

namespace App\Modules\Entity\BlogPost;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;

final class BlogPostRepository
{
    /** @var EntityRepository<BlogPost> */
    private EntityRepository $repo;
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(BlogPost::class);
        $this->em = $em;
    }

    public function getCount(): int
    {
        return $this->repo->count([]);
    }

    /** @throws Exception */
    public function getById(int $id): BlogPost
    {
        if (!$post = $this->findById($id)) {
            throw new Exception('BlogPost Not Found');
        }

        return $post;
    }

    public function findById(int $id): ?BlogPost
    {
        return $this->repo->findOneBy(['id' => $id]);
    }

    public function findBySlug(string $slug): ?BlogPost
    {
        return $this->repo->findOneBy(['slug' => $slug, 'isPublished' => true]);
    }

    /** @return BlogPost[] */
    public function findAllPublished(): array
    {
        return $this->repo->findBy(['isPublished' => true], ['createdAt' => 'DESC']);
    }

    /** @return BlogPost[] */
    public function findAll(): array
    {
        return $this->repo->findBy([], ['createdAt' => 'DESC']);
    }

    public function add(BlogPost $post): void
    {
        $this->em->persist($post);
    }

    public function remove(BlogPost $post): void
    {
        $this->em->remove($post);
    }
}
