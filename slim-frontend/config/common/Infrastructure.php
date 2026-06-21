<?php

declare(strict_types=1);

use App\Components\Flusher\FlusherInterface;
use App\Components\Flusher\Persistence\Doctrine\DoctrineFlusher;

use function DI\get;

return [
    FlusherInterface::class => get(DoctrineFlusher::class),
];
