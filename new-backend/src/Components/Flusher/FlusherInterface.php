<?php

declare(strict_types=1);

namespace App\Components\Flusher;

interface FlusherInterface
{
    public function flush(): void;
}
