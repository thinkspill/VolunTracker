<?php

return [
    'pdf' => [
        'enabled' => true,
        'binary' => '/var/www/vol/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64',
        'timeout' => false,
        'options' => [],
    ],
    'image' => [
        'enabled' => false,
        'binary' => '/usr/local/bin/wkhtmltoimage',
        'timeout' => false,
        'options' => [],
    ],
];
