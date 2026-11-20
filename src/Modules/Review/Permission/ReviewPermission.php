<?php

declare(strict_types=1);

namespace App\Modules\Review\Permission;

enum ReviewPermission: string
{
    case CREATE = 'review.create';
    case UPDATE = 'review.update';
    case DELETE = 'review.delete';
}
