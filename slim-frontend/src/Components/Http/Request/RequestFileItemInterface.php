<?php

declare(strict_types=1);

namespace App\Components\Http\Request;

interface RequestFileItemInterface
{
    public static function fromRequest(RequestFile $file, ?string $meta): self;
}
