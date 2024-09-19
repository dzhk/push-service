<?php
declare(strict_types=1);

use Src\Settings\SettingsInterface;

return [
    'logger_type' => $_ENV['LOGGER_TYPE'] ?? SettingsInterface::LOGGER_TYPE_STDOUT,
    'error' => [
        'log_level' => $_ENV['LOG_LEVEL'],
        'display_error_details' => $_ENV['DISPLAY_ERROR_DETAILS'],
        'log_errors' => $_ENV['LOG_ERRORS'],
        // Display error details in error log
        'log_error_details' => $_ENV['LOG_ERROR_DETAILS'],
    ],
    'db' => [
        'phinx_adapter' => 'mysql',
        'host' => $_ENV['MARIADB_HOST'],
        'name' => $_ENV['MARIADB_DATABASE'],
        'user' => $_ENV['MARIADB_USER'],
        'pass' => $_ENV['MARIADB_PASSWORD'],
        'port' => $_ENV['MARIADB_PORT'],
        'charset' => 'utf8mb4',
        'driver' => 'pdo_mysql'
    ],
    'redis' => [
        'host' => $_ENV['REDIS_HOST'],
        'port' => (int)$_ENV['REDIS_PORT'],
        'password' => $_ENV['REDIS_PASSWORD'],
        'timeout' => (float)$_ENV['REDIS_TIMEOUT'], // in seconds
        'read_timeout' => (float)$_ENV['REDIS_READ_TIMEOUT'], // in seconds
        'persistent_connections' => $_ENV['REDIS_PERSISTENT_CONNECTIONS'],
        'database' => (int)$_ENV['REDIS_DATABASE'],
    ],
    'redis_user_session' => [
        'host0' => $_ENV['REDIS0_HOST'],
        'host1' => $_ENV['REDIS1_HOST'],
        'host2' => $_ENV['REDIS2_HOST'],
        'host3' => $_ENV['REDIS3_HOST'],
        'host4' => $_ENV['REDIS_HOST'],
        'port0' => (int)$_ENV['REDIS0_PORT'],
        'port1' => (int)$_ENV['REDIS1_PORT'],
        'port2' => (int)$_ENV['REDIS2_PORT'],
        'port3' => (int)$_ENV['REDIS3_PORT'],
        'port4' => (int)$_ENV['REDIS_PORT'],
        'password' => $_ENV['REDIS_PASSWORD'],
        'timeout' => (float)$_ENV['REDIS_TIMEOUT'], // in seconds
        'read_timeout' => (float)$_ENV['REDIS_READ_TIMEOUT'], // in seconds
        'persistent_connections' => $_ENV['REDIS_PERSISTENT_CONNECTIONS'],
        'database' => (int)$_ENV['REDIS_DATABASE'],
    ],
    'redis_queue' => [
        'host' => $_ENV['REDIS_QUEUE_HOST'],
        'port' => (int)$_ENV['REDIS_QUEUE_PORT'],
        'password' => $_ENV['REDIS_QUEUE_PASSWORD'],
        'timeout' => (float)$_ENV['REDIS_QUEUE_TIMEOUT'], // in seconds
        'read_timeout' => (float)$_ENV['REDIS_QUEUE_READ_TIMEOUT'], // in seconds
        'persistent_connections' => $_ENV['REDIS_QUEUE_PERSISTENT_CONNECTIONS'],
        'database' => (int)$_ENV['REDIS_QUEUE_DATABASE'],
    ],
    'doctrine' => [
        'entity_path' => COMMON_SRC_DIR . '/Entity',
        'is_dev_mode' => false,
        'dest-path' => COMMON_SRC_DIR . '/Dest'
    ],
    'url_open_ssl_encoder' => ['key' => $_ENV['URL_OPEN_SSL_ENCODER_KEY'], 'algorithm' => $_ENV['URL_OPEN_SSL_ENCODER_ALGORITHM']],
    'url_producer' => $_ENV['URL_PRODUCER'] ?? 'http://127.0.0.1:3000',
    'firebase' => [
        "type" => $_ENV['FIREBASE_TYPE'],
        "project_id" => $_ENV['FIREBASE_PROJECT_ID'],
        "private_key_id" => $_ENV['FIREBASE_PRIVATE_KEY_ID'],
        "private_key" => $_ENV['FIREBASE_PRIVATE_KEY'],
        "client_email" => $_ENV['FIREBASE_CLIENT_EMAIL'],
        "client_id" => $_ENV['FIREBASE_CLIENT_ID'],
        "auth_uri" => $_ENV['FIREBASE_AUTH_URI'],
        "token_uri" => $_ENV['FIREBASE_TOKEN_URI'],
        "auth_provider_x509_cert_url" => $_ENV['FIREBASE_AUTH_PROVIDER_CERT_URL'],
        "client_x509_cert_url" => $_ENV['FIREBASE_CLIENT_CERT_URL'],
        "universe_domain" => $_ENV['FIREBASE_UNIVERSE_DOMAIN']
    ]
];