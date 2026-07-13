<?php

declare(strict_types=1);

return [
    'config' => [
        'site' => [
            'locale' => 'ru',
            'url'    => rtrim((string)(getenv('APP_URL') ?: 'http://localhost'), '/'),
            'meta'   => [
                'title'       => 'БИОФАРМ',
                'description' => 'Натуральные продукты БИОФАРМ.',
                'ogImage'     => '/assets/images/og/default.jpg',
            ],
            'contacts' => [
                'phone'   => '+7 (999) 123-45-67',
                'email'   => 'bio.active@bk.ru',
                'address' => [
                    'country'  => 'RU',
                    'region'   => 'Томская область',
                    'locality' => 'Томск',
                    'street'   => 'особая экономическая зона микрорайон Академгородок, проспект Развитие 3Е',
                ],
            ],
            'brand' => [
                'mark'     => 'БФ',
                'title'    => 'БИОФАРМ',
                'subtitle' => 'Натуральные продукты',
                'logoUrl'  => '/uploads/images/logo.png',
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
