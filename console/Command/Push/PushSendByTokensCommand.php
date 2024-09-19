<?php

namespace Console\Command\Push;

use Redis;
use Src\DBConnectors\RedisPush;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class PushSendByTokensCommand extends BasePushCommand
{

    /**
     * @var Redis
     */
    private $redis;

    private $mainPriority;
    private $currentPriority;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $this->redis = $this->redis();
    }

    protected static $defaultName = 'push:send-by-tokens';

    protected function configure()
    {
        $this->setDescription('Отправить пуши в Firebase по токенам с приоритетами')
            ->addOption(
                'priority',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Priority value from ' . self::SEND_PRIORITY_VALUES[0] .
                        ' to ' . self::SEND_PRIORITY_VALUES[count(self::SEND_PRIORITY_VALUES) - 1],
                0
            );
    }

    protected function redis(): Redis
    {
        return $this->container->get(RedisPush::class);
    }

    private function getRawDataByPriority($helpsNonMainPriority)
    {
        $this->currentPriority = $this->mainPriority;
        $rawData = $this->redis->rPop("push:send:priority:{$this->currentPriority}");
        if ($helpsNonMainPriority && !$rawData ) {
           foreach ( self::SEND_PRIORITY_VALUES as $priorityItem ) {
               if ( $priorityItem == $this->mainPriority ) {
                   continue;
               }
               $this->currentPriority = $priorityItem;
               $rawData = $this->redis->rPop("push:send:priority:{$this->currentPriority}");
               if ( $rawData ) {
                   return $rawData;
               }
           }
        }
        return $rawData;
    }
    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $priority = $input->getOption('priority');
        if ($priority !== '' && in_array($priority, array_keys(self::SEND_PRIORITY_VALUES))) {
            $this->mainPriority = self::SEND_PRIORITY_VALUES[$priority];
        } else {
            $this->mainPriority = self::SEND_PRIORITY_VALUES[0];
        }

        $this->currentPriority = $this->mainPriority;

        $start = microtime(true);
        $lap = 0;
        while ($lap < 60) {
            $lap = microtime(true) - $start;
            $rawData = $this->getRawDataByPriority(true);
            if ($rawData === false) {
                sleep(1);
                continue;
            }
            $messageData = $rawData;

            try {
//                $logContext = [
//                    'priority' => $this->currentPriority,
//                    'push' => $messageData['message']['data']['id'],
//                    'tokens' => $messageData['tokens'],
//                    'domain' => $messageData['queueData']['domain'],
//                ];
//                $this->logger()->info(json_encode($logContext));

                $batchSize = 450;
                $batches = array_chunk($messageData['tokens'], $batchSize);

                foreach ($batches as $tokens) {
                    $this->logger()->info('tokens count: '. count($tokens));
                    $report = $this->firebase->sendToMany($tokens, $messageData['message']);

                    $this->logger()->info('success result: '. $report->successes()->count());

                    if ($report->hasFailures()) {
                        foreach ($report->failures()->getItems() as $failure) {
                            $message = $failure->error()->getMessage();
                            if (!strripos($message, 'Requested entity was not found') < 0 ) {
                                $this->logger()->warning($message);
                            }
                        }
                    }

                    $successfulTargets = $report->validTokens(); // string[]

                    if ( count($successfulTargets) > 0 ) {
                        $this->logger()->info(
                            'successTargets: ' . count($successfulTargets)
                        );
                    }

                    // Invalid (=malformed) tokens
                    $invalidTargets = $report->invalidTokens(); // string[]
                    if ( count($invalidTargets) > 0 ) {
                        $this->logger()->warning(
                            'invalidTargets: '. count($invalidTargets),
                            $invalidTargets
                        );
                    }

                    // Unknown tokens are tokens that are valid but not know to the currently
                    // used Firebase project. This can, for example, happen when you are
                    // sending from a project on a staging environment to tokens in a
                    // production environment

                    $unknownTargets = $report->unknownTokens(); // string[]
                    if ( count($unknownTargets) > 0 ) {
                        $this->logger()->warning(
                            'unknownTargets: ' . count($unknownTargets),
                           // $unknownTargets
                        );
                    }
                }

            } catch (\Throwable $exception) {
                $this->logger()->warning("Error send push by tokens for domain {$messageData['queueData']['domain']}: ".$exception->getMessage());
            }

            $lap = microtime(true) - $start;
        }

        $this->logger()->info('Done');
        return 0;
    }
}