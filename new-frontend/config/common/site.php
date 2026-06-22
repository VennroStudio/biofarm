<?php

declare(strict_types=1);

return [
    'config' => [
        'site' => [
            'locale' => 'ru',
            'meta'   => [
                'title'       => 'Slim Frontend',
                'description' => 'PHP/Twig frontend with React islands.',
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
            'footer' => [
                'title'       => 'Slim Frontend Template',
                'description' => 'SEO HTML renders on PHP/Twig. Dynamic controls mount as React islands.',
                'links'       => [
                    [
                        'label' => 'Home',
                        'href'  => '/',
                    ],
                ],
            ],
        ],
    ],
];
