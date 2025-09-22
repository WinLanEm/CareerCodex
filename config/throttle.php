<?php
return [
    'limits' => [
        'github' => [
            'allow' => 50,
            'every' => 60,
            'block' => 10,
            'retryAfter' => 30,
        ],
        'gitlab' => [
            'allow' => 50,
            'every' => 60,
            'block' => 10,
            'retryAfter' => 30,
        ],
        'bitbucket' => [
            'allow' => 20,
            'every' => 60,
            'block' => 10,
            'retryAfter' => 30,
        ],
        'jira' => [
            'allow' => 20,
            'every' => 60,
            'block' => 10,
            'retryAfter' => 30,
        ],
        'asana' => [
            'allow' => 150,
            'every' => 60,
            'block' => 10,
            'retryAfter' => 30,
        ],
    ]
];
