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
                'mark'     => 'SF',
                'title'    => 'Slim Frontend',
                'subtitle' => 'Twig pages + React islands',
            ],
            'navigation' => [
                [
                    'label' => 'Home',
                    'href'  => '/',
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
