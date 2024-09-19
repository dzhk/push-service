<?php

namespace Console\Command\Push;

use Console\Command\BaseCommand;
use PDO;
use Src\DBConnectors\RedisPush;
use Redis;
use Src\Service\FirebaseAPIService;

abstract class BasePushCommand extends BaseCommand
{
    const SEND_PRIORITY_VALUES = [
        0,
        1,
        2,
        3,
        4,
        5
    ];

    protected $firebase;

    protected static $defaultName = 'push:base-command';

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->firebase = $this->container->get(FirebaseAPIService::class);
    }

    protected function redis(): Redis
    {
        return $this->container->get(Redis::class);
    }


    /**
     * @throws \RedisException
     * @throws \PDOException
     */
    protected function getDistinctFcmField(string $field, $forceRewriteRedis = false)
    {
        $availableFields = [
            'tz_offset',
            'domain',
            'scheduled_at_offset'
        ];

        if (!in_array($field, $availableFields)) {
            return [];
        }
        $now = new \DateTimeImmutable();
        $redisKey = 'fcm:' . $field . ':' . $now->format('Ymd');
        if ($forceRewriteRedis || !$fields = $this->redis()->get($redisKey)) {
            $sth = $this->pdo()->prepare(
                "SELECT DISTINCT {$field} FROM fcm_token ORDER BY {$field}"
            );
            $sth->execute();
            $fields = $sth->fetchAll(PDO::FETCH_COLUMN);
            $this->redis()->set($redisKey, $fields, ['EX' => 3600 * 24]);
        }
        return $fields;
    }

    protected function topicName(
        $timezoneOffset,
        $additionalOffsetInMinutes,
        string $domain = 'dddddddd'
    ): string {
        $strTzOffset = (string)$timezoneOffset;
        $strAddOffset = (string)$additionalOffsetInMinutes;

        return $domain . '_' . $this->topicNameWithoutDomain($strTzOffset, $strAddOffset);
    }

    protected function topicNameWithoutDomain($timezoneOffset, $additionalOffsetInMinutes): string
    {
        $strTzOffset = (string)$timezoneOffset;
        $strAddOffset = (string)$additionalOffsetInMinutes;

        return $strTzOffset . '_ao_' . $strAddOffset;
    }
}