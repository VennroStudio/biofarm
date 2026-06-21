<?php

declare(strict_types=1);

namespace App\Modules\Blog\Permission;

enum BlogPermission: string
{
    case CREATE = 'blog.create';
    case UPDATE = 'blog.update';
    case DELETE = 'blog.delete';
}
