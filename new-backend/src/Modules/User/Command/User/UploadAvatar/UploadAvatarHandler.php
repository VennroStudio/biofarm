<?php

declare(strict_types=1);

namespace App\Modules\User\Command\User\UploadAvatar;

use App\Components\Cacher\Cacher;
use App\Components\Exception\AccessDeniedException;
use App\Components\Flusher\FlusherInterface;
use App\Components\Storage\FileUploaderService;
use App\Components\Storage\ImageFileValidator;
use App\Components\Storage\StorageInterface;
use App\Modules\User\Entity\User\Fields\Enums\UserRole;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Permission\UserPermission;
use App\Modules\User\Service\UserPermissionService;
use DateMalformedStringException;
use Random\RandomException;

final readonly class UploadAvatarHandler
{
    public function __construct(
        private UserRepository        $userRepository,
        private UserPermissionService $userPermissionService,
        private FileUploaderService   $uploader,
        private StorageInterface      $storage,
        private FlusherInterface      $flusher,
        private ImageFileValidator    $fileValidator,
        private Cacher                $cacher,
    ) {}

    /**
     * @throws DateMalformedStringException
     * @throws AccessDeniedException
     * @throws RandomException
     */
    public function handle(UploadAvatarCommand $command): string
    {
        $user = $this->userRepository->getById($command->userId);

        $this->userPermissionService->check(
            currentUserId: $command->currentUserId,
            currentUserRole: UserRole::from($command->currentUserRole),
            userId: $command->userId,
            action: UserPermission::UPDATE,
        );

        $newAvatarPath = $this->uploader->upload(
            tmpFilePath: $command->tmpFilePath,
            destinationDir: "user/{$command->userId}/avatar",
            validator: $this->fileValidator,
            oldFilePath: $user->avatar
        );

        $user->setAvatar($newAvatarPath);

        $this->cacher->delete('user_identity_' . $command->userId);

        $this->flusher->flush();

        return $this->storage->url($newAvatarPath);
    }
}
