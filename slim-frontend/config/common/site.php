<?php

declare(strict_types=1);

return [
    'config' => [
        'site' => [
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
