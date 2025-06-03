<?php
return [
    'routes' => [
        '' => '',
    ],
    'caching' => [
        'driver' => 'file',
        'file' => [
            'store_dir' => '/tmp/small-framework-file-cache-driver'
        ],
    ],
    'cookie-enc-key' => "random-string-for-the-tests",
    'OWN_404_HANDLER' => false,
    'test-param' => "test-value",
];