<?php

declare(strict_types=1);

namespace App\Modules\Order\Permission;

enum OrderPermission: string
{
    case CREATE = 'order.create';
    case UPDATE = 'order.update';
    case DELETE = 'order.delete';
}
