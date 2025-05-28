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
    'OWN_404_HANDLER' => false,
    'test-param' => "test-value",
];