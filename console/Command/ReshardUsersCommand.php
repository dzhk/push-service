<?php
declare(strict_types=1);

namespace Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Redis;
use Symfony\Component\Console\Output\OutputInterface;

final class ReshardUsersCommand extends BaseCommand
{

    protected static $defaultName = 'users-sessions:reshard';

    private Redis $redis;
    private Redis $redis0;
    private Redis $redis1;
    private Redis $redis2;
    private Redis $redis3;

    public function __construct($settings, $redis, $logger, ...$args)
    {
        parent::__construct(...$args);
        $this->redis = $redis;

        $this->redis0 = $this->newRedis($settings->get('redis_user_session'), 0);
        $this->redis1 = $this->newRedis($settings->get('redis_user_session'), 1);
        $this->redis2 = $this->newRedis($settings->get('redis_user_session'), 2);
        $this->redis3 = $this->newRedis($settings->get('redis_user_session'), 3);

        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setDescription('Reshard user sessions from one redis to four');
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $iterator = null;
        $pattern = 'euid:*';
        $scanned = 0;
        do {
            $response = $this->redis->scan($iterator, $pattern, 10000);
            if ($response !== false && \count($response) > 0) {
                foreach ($response as $extUId) {
                    $uId = $this->redis->get($extUId);
                    if (!$uId) {
                        $this->logger->info('not found extUid', ['extUserId' => $extUId]);
                        continue;
                    }

                    $shard = crc32($uId) % 5;
                    if ($shard === 4) {
                        continue;
                    }

                    $userData = $this->redis->get('uid:' . $uId);
                    if (!$userData) {
                        $this->logger->info('not found userData', ['extUserId' => $extUId, 'userId' => $uId]);
                        continue;
                    }
//                    $this->logger->info('got', ['extUserId' => $extUId, 'userId' => $uId, 'shard' => $shard, 'userData' => $userData]);

                    $propVal = "redis" . $shard;
                    $pipe = $this->$propVal->pipeline();
                    $pipe->set($extUId, $uId);
                    $pipe->set('uid:' . $uId, json_encode($userData));
                    $lhtToStartOfMin = (int)($userData['lht'] / 60) * 60;
                    $pipe->sAdd((string)$lhtToStartOfMin, $uId);
                    $res = $pipe->exec();
                }
                $scanned += count($response);
            }
            $this->logger->info('tokens', ['scanned' => $scanned]);
        } while ($iterator > 0);

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
