<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CMS Modules Configuration
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'news' => [
            'label' => 'Tin tức & Bài viết',
            'scope' => 'post',
            'icon' => 'bi-newspaper',
            'nav_posts' => 'news_posts',
            'nav_categories' => 'news_categories',
            'nav_tags' => 'news_tags',
        ],
        'events' => [
            'label' => 'Sự kiện',
            'scope' => 'events',
            'icon' => 'bi-calendar-event',
            'nav_posts' => 'events_posts',
            'nav_categories' => 'events_categories',
            'nav_tags' => 'events_tags',
        ],
        'schools' => [
            'label' => 'Trường học',
            'scope' => 'school',
            'icon' => 'bi-mortarboard',
            'nav_posts' => 'schools_posts',
            'nav_categories' => 'schools_categories',
            'nav_tags' => 'schools_tags',
        ],
        'majors' => [
            'label' => 'Ngành học',
            'scope' => 'major',
            'icon' => 'bi-book',
            'nav_posts' => 'majors_posts',
            'nav_categories' => 'majors_categories',
            'nav_tags' => 'majors_tags',
        ],
    ],
];
