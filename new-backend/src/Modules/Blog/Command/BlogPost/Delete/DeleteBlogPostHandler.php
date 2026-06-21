<?php

declare(strict_types=1);

namespace App\Modules\Blog\Command\BlogPost\Delete;

use App\Components\Cacher\Cacher;
use App\Components\Flusher\FlusherInterface;
use App\Modules\Blog\Entity\BlogPost\BlogPostRepository;
use App\Modules\Blog\Permission\BlogPermission;
use App\Modules\Blog\Service\BlogPermissionService;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use DateMalformedStringException;

final readonly class DeleteBlogPostHandler
{
    public function __construct(
        private BlogPostRepository $blogPostRepository,
        private BlogPermissionService $permissionService,
        private Cacher $cacher,
        private FlusherInterface $flusher,
    ) {}

    /**
     * @throws DateMalformedStringException
     */
    public function handle(DeleteBlogPostCommand $command): void
    {
        $this->permissionService->checkRole(
            currentUserRole: UserRole::from($command->currentUserRole),
            action: BlogPermission::DELETE,
        );

        $post = $this->blogPostRepository->getById($command->postId);
        $post->markDeleted();

        $this->cacher->deleteTag('blog_posts');
        $this->cacher->delete('blog_post_by_id_' . $command->postId);
        $this->cacher->delete('blog_post_by_slug_' . $post->slug);
        $this->flusher->flush();
    }
}
