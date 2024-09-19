<?php
declare(strict_types=1);

namespace Console\Command;

use Redis;
use Src\Settings\SettingsInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class InvalidateSessionsCommand extends BaseCommand
{
    const SESSION_DURATION_DAYS = 30;
    const DEFAULT_OFFSET_MINUTES = 30;
    const KEY_PREFIX_EXT_USER_ID = "euid:";
    const KEY_PREFIX_USER_ID = "uid:";
    const KEY_PREFIX_LAST_HIT_TIME = "lh:";

    private Redis $redis;
    private $redisSetting;

    protected static $defaultName = 'users-sessions:invalidate';

    public function __construct(SettingsInterface $settings, ...$args)
    {
        $this->redisSetting = $settings->get('redis_user_session');
        parent::__construct(...$args);
        $this->addOption(
            'offsetMinutes',
            'o',
            InputOption::VALUE_OPTIONAL,
            'Offset in minutes from the main time for which we check sessions', self::DEFAULT_OFFSET_MINUTES
        );
        $this->addOption(
            'shard',
            's',
            InputOption::VALUE_OPTIONAL,
            'Redis session shard (0-4)', 4
        );

    }

    protected function configure()
    {
        $this->setDescription('Invalidate user sessions in redis');
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $offsetMinutes = $input->getOption('offsetMinutes');
        $shard  = $input->getOption('shard');
        $offsetMinutes = !$offsetMinutes
            ? self::DEFAULT_OFFSET_MINUTES
            : (abs((int)$offsetMinutes) ?: self::DEFAULT_OFFSET_MINUTES);
        $this->logger()->info('Clean session. TTL: ' . self::SESSION_DURATION_DAYS . ' days; offset: ' . $offsetMinutes . ' min, shard:' . $shard);
        $this->redis = $this->newRedis($this->redisSetting, $shard);
        $currentTimestamp = time();
        $expirationTimestamp = (int)(($currentTimestamp - self::SESSION_DURATION_DAYS * 86400) / 60) * 60;
        $keys = [];

        for ($i = 0; $i <= $offsetMinutes; $i++) {
            $timestamp = $expirationTimestamp - ($i * 60);
            $keys[] = self::KEY_PREFIX_LAST_HIT_TIME . $timestamp;
        }

        $cntDeletedIntervals = 0;
        $cntDeleted = 0;
        $cntMoved = 0;
        foreach ($keys as $key) {
            $uids = $this->redis->smembers($key);
            foreach ($uids as $uid) {
                try {
                    $uidKey = self::KEY_PREFIX_USER_ID . $uid;
                    $userInfo = $this->redis->get($uidKey);

                    if (is_string($userInfo)) {
                        $userInfo = json_decode($userInfo, true);
                    }

                    if (!$userInfo) {
                        $this->logger()->notice("There is no information for key: {$uidKey}");
                        continue;
                    }

                    if ($userInfo['lht'] < $expirationTimestamp) {
                        $this->redis->del($uidKey);
                        $euidKey = self::KEY_PREFIX_EXT_USER_ID . $userInfo['oid'] . ':' . $userInfo['euid'];
                        $this->redis->del($euidKey);
                        $cntDeleted++;
                    } else {
                        $newKey = self::KEY_PREFIX_LAST_HIT_TIME . ((int)($userInfo['lht'] / 60) * 60);
                        $this->redis->sadd($newKey, $uid);
                        $cntMoved++;
                    }

                } catch (\Throwable $exception) {
                    $message = "Error occurred in [" . $exception->getFile() . "] at line [" . $exception->getLine() . "]: [" . $exception->getMessage() . "]\n\n" .
                        $exception->getTraceAsString();
                    $this->logger()->notice("Error for key {$uidKey}.\n\n" . $message);
                }
            }
            if (count($uids) > 0) {
                $cntDeletedIntervals++;
            }
            $this->redis->del($key);
        }
        $this->logger()->notice("Deleted intervals: {$cntDeletedIntervals}");
        $this->logger()->notice("Deleted users: {$cntDeleted}");
        $this->logger()->notice("Users moved for another interval: {$cntMoved}");
        return 0;
    }

    private function newRedis($settings, $shard)
    {

        $redis = new Redis();
        $redis->connect(
            $settings['host' . $shard], $settings['port' . $shard],
            $settings['timeout'], null,
            0, $settings['read_timeout']
        );
        $redis->auth($settings['password']);
        $redis->select(0);
        return $redis;
    }
}
