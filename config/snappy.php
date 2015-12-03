<?php

return array(
    'pdf' => array(
        'enabled' => true,
        'binary' => '/var/www/vol/vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64',
        'timeout' => false,
        'options' => array(),
    ),
    'image' => array(
        'enabled' => false,
        'binary' => '/usr/local/bin/wkhtmltoimage',
        'timeout' => false,
        'options' => array(),
    ),
);
