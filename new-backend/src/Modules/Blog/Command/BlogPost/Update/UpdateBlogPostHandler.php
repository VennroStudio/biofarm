<?php

declare(strict_types=1);

namespace App\Modules\Blog\Command\BlogPost\Update;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\String\SlugGenerator;
use App\Modules\Blog\Entity\BlogPost\BlogPostRepository;
use App\Modules\Blog\Permission\BlogPermission;
use App\Modules\Blog\Service\BlogPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;
use DateTimeImmutable;

final readonly class UpdateBlogPostHandler
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private BlogPermissionService $permissionService,
        private SlugGenerator $slugGenerator,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(UpdateBlogPostCommand $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: BlogPermission::UPDATE,
        );

        $post = $this->blogPostRepository->getById($command->postId);
        $oldSlug = $post->slug;
        $slug = $this->slug($command->slug, $command->title);
        $existing = $this->blogPostRepository->findBySlug($slug);

        if ($existing !== null && $existing->id !== $post->id) {
            throw new DomainExceptionModule(
                module: 'blog',
                message: 'error.blog_post_slug_already_exists',
                code: 2,
            );
        }

        $post->edit(
            slug: $slug,
            title: trim($command->title),
            excerpt: trim($command->excerpt),
            content: $command->content,
            image: trim($command->image),
            categoryId: trim($command->categoryId),
            authorName: trim($command->authorName),
            readTime: $command->readTime,
            isPublished: $command->isPublished,
            h1: $command->h1,
            seoTitle: $command->seoTitle,
            seoDescription: $command->seoDescription,
            imageAlt: $command->imageAlt,
            publishedAt: $this->publishedAt($command->publishedAt),
        );

        $this->deleteCache($command->postId, $oldSlug, $slug);
        $this->flusher->flush();
    }

    private function slug(?string $slug, string $title): string
    {
        $slug = $slug !== null && trim($slug) !== ''
            ? trim($slug)
            : $this->slugGenerator->generate($title);

        return $this->slugGenerator->generate($slug);
    }

    private function publishedAt(?string $publishedAt): ?DateTimeImmutable
    {
        if ($publishedAt === null || trim($publishedAt) === '') {
            return null;
        }

        return new DateTimeImmutable($publishedAt);
    }

    private function deleteCache(int $postId, string $oldSlug, string $newSlug): void
    {
        $this->cacher->deleteTag('blog_posts');
        $this->cacher->delete('blog_post_by_id_' . $postId);
        $this->cacher->delete('blog_post_by_slug_' . $oldSlug);
        $this->cacher->delete('blog_post_by_slug_' . $newSlug);
    }
}
