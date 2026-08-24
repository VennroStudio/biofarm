<?php

declare(strict_types=1);

return [
    'error.category_not_found'                     => 'ProductCategory not found.',
    'error.category_slug_already_exists'           => 'ProductCategory with this slug already exists.',
    'error.category_is_deleted'                    => 'ProductCategory is deleted.',
    'error.category_has_products'                  => 'Cannot delete category with products.',
    'error.category_has_children'                  => 'Cannot delete category with child categories.',
    'error.category_parent_not_found'              => 'Parent category not found.',
    'error.category_parent_cannot_be_self'         => 'Category cannot be its own parent.',
    'error.category_parent_cycle'                  => 'Cannot select a child category as parent.',
    'error.product_not_found'                      => 'Product not found.',
    'error.product_slug_already_exists'            => 'Product with this slug already exists.',
    'error.product_create_failed'                  => 'Failed to create product.',
    'error.product_is_deleted'                     => 'Product is deleted.',
    'error.attribute_name_required'                => 'Attribute name is required.',
    'error.attribute_slug_already_exists'          => 'Attribute with this slug already exists.',
    'error.attribute_filter_prefix_already_exists' => 'Filter prefix is already used by another attribute.',
    'error.attribute_required'                     => 'Attribute is required.',
    'error.attribute_value_name_required'          => 'Attribute value name is required.',
    'error.attribute_value_not_found'              => 'Attribute value not found.',
    'error.attribute_not_found'                    => 'Attribute not found.',
    'error.attribute_value_slug_already_exists'    => 'Attribute value with this slug already exists in this attribute.',
    'error.product_group_name_required'            => 'Product group name is required.',
    'error.product_group_not_found'                => 'Product group not found.',
];
