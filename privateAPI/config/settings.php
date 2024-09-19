<?php
declare(strict_types=1);

$common = require COMMON_SRC_DIR . '/config/settings.php';
$env = [
    'jwt' => [
        'private_key' => $_ENV['JWT_PRIVATE_KEY'],
        'public_key' => $_ENV['JWT_PUBLIC_KEY'],
        'algorithm' => 'RS256'
    ]
];

return array_merge($common, $env);