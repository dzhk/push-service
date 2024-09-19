<?php
declare(strict_types=1);

namespace Src\Settings;

interface SettingsInterface
{
    const LOGGER_TYPE_STDOUT = 'stdout';
    const LOGGER_TYPE_OLTP = 'oltp';
    const LOGGER_TYPES = [
        self::LOGGER_TYPE_OLTP => self::LOGGER_TYPE_OLTP,
        self::LOGGER_TYPE_STDOUT => self::LOGGER_TYPE_STDOUT,

    ];

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key = '');
}