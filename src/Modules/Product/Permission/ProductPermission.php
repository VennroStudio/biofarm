<?php

declare(strict_types=1);

namespace App\Modules\Product\Permission;

enum ProductPermission: string
{
    case CREATE = 'product.create';
    case UPDATE = 'product.update';
    case DELETE = 'product.delete';
}
