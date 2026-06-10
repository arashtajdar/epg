<?php

return [
    'iran_intl' => [
        'type' => 'html',
        'url' => 'https://www.iranintl.com/tvschedule',
        'parser' => 'iran_international.php',
    ],
    'dummy_api' => [
        'type' => 'api',
        'url' => 'https://jsonplaceholder.typicode.com/todos', // using a real dummy API for the test
        'parser' => 'dummy_api.php',
    ]
];
