<?php

$common = require COMMON_SRC_DIR . '/config/settings.php';
$env = [
    'push_logs' => [
        'enable' => $_ENV['PUSH_LIFE_LOGS_ENABLE'],
        'percentage' => $_ENV['PUSH_LIFE_LOGS_PERCENTAGE'],
    ],
    'push_public_domain' => $_ENV['PUSH_PUBLIC_DOMAIN'] ?? false
];

return array_merge($common, $env);