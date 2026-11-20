<?php

declare(strict_types=1);

namespace App\Components\Frontend;

enum FrontendUrlType: string
{
    case MAIN = 'main';
    case AUTH = 'auth';
}
