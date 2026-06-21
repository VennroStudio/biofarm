<?php

declare(strict_types=1);

use App\Modules\User\Entity\User\Persistence\Doctrine\DoctrineUserRepository;
use App\Modules\User\Entity\User\UserRepository;
use App\Modules\User\Entity\UserToken\Persistence\Doctrine\DoctrineUserTokenRepository;
use App\Modules\User\Entity\UserToken\UserTokenRepository;

use function DI\get;

return [
    UserRepository::class      => get(DoctrineUserRepository::class),
    UserTokenRepository::class => get(DoctrineUserTokenRepository::class),
];
