<?php
declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

const APP_DIR = __DIR__ . '/..';

if (file_exists(APP_DIR . '/.env')) {
    (new Dotenv())->overload(APP_DIR . '/.env');
}
const COMMON_SRC_DIR = APP_DIR . '/../src';
$config = require '../config/settings.php';
$dbConfig = $config['db'];
return
    [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/../db/migrations',
            'seeds' => '%%PHINX_CONFIG_DIR%%/../db/seeds'
        ],
        'environments' => [
            'default_migration_table' => 'migrations',

            // default_environment всегда production. остальное контролируем за счет ENV и конфигов
            'default_environment' => 'production',
            'production' => [
                'adapter' => $dbConfig['phinx_adapter'],
                'host' => $dbConfig['host'],
                'name' => $dbConfig['name'],
                'user' => $dbConfig['user'],
                'pass' => $dbConfig['pass'],
                'port' => $dbConfig['port'],
                'charset' => $dbConfig['charset'],
            ]
        ],
        'version_order' => 'creation'
    ];
