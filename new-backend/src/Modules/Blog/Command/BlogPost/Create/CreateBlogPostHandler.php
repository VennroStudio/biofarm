<?php

declare(strict_types=1);

namespace App\Modules\Blog\Command\BlogPost\Create;

use App\Components\Cacher\Cacher;
use App\Components\Exception\DomainExceptionModule;
use App\Components\Flusher\FlusherInterface;
use App\Components\String\SlugGenerator;
use App\Modules\Blog\Entity\BlogPost\BlogPost;
use App\Modules\Blog\Entity\BlogPost\BlogPostRepository;
use App\Modules\Blog\Permission\BlogPermission;
use App\Modules\Blog\Service\BlogPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class CreateBlogPostHandler
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
    public function handle(CreateBlogPostCommand $command): int
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: BlogPermission::CREATE,
        );

        $slug = $this->slug($command->slug, $command->title);
        $this->assertSlugFree($slug);

        $post = BlogPost::create(
            slug: $slug,
            title: trim($command->title),
            excerpt: trim($command->excerpt),
            content: $command->content,
            image: trim($command->image),
            categoryId: trim($command->categoryId),
            authorName: trim($command->authorName),
            readTime: $command->readTime,
            isPublished: $command->isPublished,
        );

        $this->blogPostRepository->add($post);
        $this->deleteListCache();
        $this->flusher->flush();

        return (int)$post->id;
    }

    private function slug(?string $slug, string $title): string
    {
        $slug = $slug !== null && trim($slug) !== ''
            ? trim($slug)
            : $this->slugGenerator->generate($title);

        return $this->slugGenerator->generate($slug);
    }

    private function assertSlugFree(string $slug): void
    {
        if ($this->blogPostRepository->findBySlug($slug) !== null) {
            throw new DomainExceptionModule(
                module: 'blog',
                message: 'error.blog_post_slug_already_exists',
                code: 2,
            );
        }
    }

    private function deleteListCache(): void
    {
        $this->cacher->deleteTag('blog_posts');
    }
}
