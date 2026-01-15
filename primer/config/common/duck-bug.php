<?php

declare(strict_types=1);

use DuckBug\Duck;

return [
    Duck::class => static fn (): Duck => Duck::get(),
];
