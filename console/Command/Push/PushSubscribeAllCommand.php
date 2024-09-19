<?php

namespace Console\Command\Push;

use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * shuf topics.csv > topics_shuf.csv && split -n 8 topics_shuf.csv --additional-suffix=.csv topics_
 */
final class PushSubscribeAllCommand extends BasePushCommand
{
    protected static $defaultName = 'push:subscribe-all';

    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    protected function configure()
    {
        $this->setDescription('Подписываем на топики с учётом домена, часового пояса и отступа (scheduled_at_offset)')
            ->addOption(
                'domains',
                'd',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'For what domain execute subscription to topics',
                []
            )
            ->addOption(
                'from_date',
                'f',
                InputOption::VALUE_OPTIONAL,
                'From what date select tokens (Y-m-d ex. 2024-01-01 00:00:00)',
                (new \DateTimeImmutable('yesterday'))->setTime(0, 0)->format('Y-m-d H:i:s')
            )
            ->addOption(
                'file',
                '',
                InputOption::VALUE_OPTIONAL,
                'From what date select tokens (Y-m-d ex. 2024-01-01 00:00:00)',
                ''
            );
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $scheduledAtOffsets = $this->getDistinctFcmField('scheduled_at_offset');
        $timezoneOffsets = $this->getDistinctFcmField('tz_offset');
        if (count($input->getOption('domains')) === 0) {
            $domains = $this->getDistinctFcmField('domain');
        } else {
            $domains = $input->getOption('domains');
        }

        $fromDate = $input->getOption('from_date');
        $file = 'topics.csv';
        if ($input->getOption('file') !== '') {
            $file = $input->getOption('file');
        }

//        unsubscribe previous timezone topics (if they exist) it's need because tz can change
//        $this->unsubscribeFromTzAndOffsets($timezoneOffsets, $scheduledAtOffsets);

        $this->subscribe($domains, $timezoneOffsets, $scheduledAtOffsets, $fromDate, $file);
        return 0;
    }

    protected function subscribe($domains, $timezoneOffsets, $additionalOffsets, $fromDate, $fileName)
    {
        $this->logger()->info($this->colorizeText("subscribing..."));
        $file = fopen(__DIR__ . '/../../app/' . $fileName, 'rb');
        $l = 0;
        while (($line = fgetcsv($file)) !== false) {
            $l++;
            $this->logger()->info("строка: " . $l);
            $domain = $line[0];
            $scheduledAtOffset = $line[1];
            $timezoneOffset = $line[2];

            $topic = $this->topicName($timezoneOffset, $scheduledAtOffset, $domain);
            $offset = 0;
            while ($offset >= 0) {
                $sth = $this->pdo()->prepare('
                               SELECT token 
                                 FROM `fcm_token`
                                WHERE `updated_at` > :dateTime 
                                      AND `tz_offset` = :timezoneOffset 
                                      AND `scheduled_at_offset` = :scheduledAtOffset 
                                      AND `domain` = :domain
                                      AND `unsub` = 0  
                             ORDER BY `updated_at`
                                LIMIT 1000
                               OFFSET :offset
                        ');
                $sth->bindParam(':dateTime', $fromDate);
                $sth->bindParam(':scheduledAtOffset', $scheduledAtOffset);
                $sth->bindParam(':timezoneOffset', $timezoneOffset);
                $sth->bindParam(':domain', $domain);
                $sth->bindParam(':offset', $offset, PDO::PARAM_INT);

                $sth->execute();
                $tokens = $sth->fetchAll(PDO::FETCH_COLUMN);

                if (\count($tokens)) {
                    $this->logger()->info(
                        $this->colorizeText(" ...количество токенов: " . count($tokens)),
                        [$topic]
                    );
                    $offset += \count($tokens);
                    $res = $this->firebase->subscribeTo($topic, $tokens);
                    $this->logger()->info($this->colorizeText(json_encode($res)));
                } else {
                    $offset = -1;
                }
            }
        }
        fclose($file);
    }
}