<?php

require_once __DIR__ . '/../vendor/autoload.php';
const APP_DIR = __DIR__ . '/..';
const COMMON_SRC_DIR = APP_DIR . '/../src';

use Symfony\Component\Dotenv\Dotenv;

chdir(dirname(__DIR__));

if (file_exists(APP_DIR . '/.env')) {
    (new Dotenv())->overload(APP_DIR . '/.env');
}
