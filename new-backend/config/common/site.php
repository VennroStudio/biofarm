<?php

declare(strict_types=1);

return [
    'config' => [
        'site' => [
            'locale' => 'ru',
            'meta'   => [
                'title'       => 'БИОФАРМ',
                'description' => 'Натуральные продукты БИОФАРМ.',
            ],
            'brand' => [
                'mark'     => 'БФ',
                'title'    => 'БИОФАРМ',
                'subtitle' => 'Натуральные продукты',
                'logoUrl'  => 'https://biofarm.store/uploads/images/logo.png',
            ],
            'navigation' => [
                [
                    'label' => 'Сотрудничество',
                    'href'  => '/#partner',
                ],
                [
                    'label' => 'Каталог',
                    'href'  => '/#catalog',
                ],
                [
                    'label' => 'Блог',
                    'href'  => '/#blog',
                ],
                [
                    'label' => 'О нас',
                    'href'  => '/#about',
                ],
                [
                    'label' => 'Отзывы',
                    'href'  => '/#reviews',
                ],
                [
                    'label' => 'Контакты',
                    'href'  => '/#contacts',
                ],
            ],
        ],
    ],
];
