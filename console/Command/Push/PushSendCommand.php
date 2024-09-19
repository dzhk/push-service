<?php

namespace Console\Command\Push;

use Redis;
use Src\DBConnectors\RedisPush;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PushSendCommand extends BasePushCommand
{

    /**
     * @var Redis
     */
    private $redis;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->redis = $this->redis();
    }

    protected static $defaultName = 'push:send';

    protected function configure()
    {
        $this->setDescription('Отправить пуши в Firebase по топикам');
    }

    protected function redis(): Redis
    {
        return $this->container->get(RedisPush::class);
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);
        $lap = 0;
        while ($lap < 60) {
            $rawData = $this->redis->rPop('push:send');
            if ($rawData === false) {
                sleep(1);
                continue;
            }
            $data = json_decode($rawData, true);

            $res = $this->firebase->sendToTopic($data['topic'], $data['message']);

            $logContext = ['push' => $data['message']['data']['id'], 'topic' => $data['topic']];

            if ( isset($data['isDebug']) ) {
                $logContext['debug'] = true;
                $logContext['uuid'] = $data['uuid'] ?? 'oops uuid is not set';
            }

            $this->logger()->info(json_encode($res), $logContext);
            $lap = microtime(true) - $start;
        }

        $this->logger()->info('Done');
        return 0;
    }
}