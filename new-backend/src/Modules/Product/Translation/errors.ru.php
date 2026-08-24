<?php

declare(strict_types=1);

return [
    'error.category_not_found'                     => 'Категория не найдена.',
    'error.category_slug_already_exists'           => 'Категория с таким slug уже существует.',
    'error.category_is_deleted'                    => 'Категория удалена.',
    'error.category_has_products'                  => 'Нельзя удалить категорию, в которой есть товары.',
    'error.category_has_children'                  => 'Нельзя удалить категорию, у которой есть подкатегории.',
    'error.category_parent_not_found'              => 'Родительская категория не найдена.',
    'error.category_parent_cannot_be_self'         => 'Категория не может быть родителем самой себя.',
    'error.category_parent_cycle'                  => 'Нельзя выбрать дочернюю категорию родителем.',
    'error.product_not_found'                      => 'Товар не найден.',
    'error.product_slug_already_exists'            => 'Товар с таким slug уже существует.',
    'error.product_create_failed'                  => 'Не удалось создать товар.',
    'error.product_is_deleted'                     => 'Товар удален.',
    'error.attribute_name_required'                => 'Укажите название атрибута.',
    'error.attribute_slug_already_exists'          => 'Атрибут с таким slug уже существует.',
    'error.attribute_filter_prefix_already_exists' => 'Префикс фильтра уже используется другим атрибутом.',
    'error.attribute_required'                     => 'Укажите атрибут.',
    'error.attribute_value_name_required'          => 'Укажите название значения атрибута.',
    'error.attribute_value_not_found'              => 'Значение атрибута не найдено.',
    'error.attribute_not_found'                    => 'Атрибут не найден.',
    'error.attribute_value_slug_already_exists'    => 'Значение с таким slug уже существует в этом атрибуте.',
    'error.product_group_name_required'            => 'Укажите название группы товаров.',
    'error.product_group_not_found'                => 'Группа товаров не найдена.',
];
