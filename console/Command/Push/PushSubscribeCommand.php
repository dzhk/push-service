<?php

namespace Console\Command\Push;

use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * shuf topics.csv > topics_shuf.csv && split -n 8 topics_shuf.csv --additional-suffix=.csv topics_
 */
final class PushSubscribeCommand extends BasePushCommand
{
    protected static $defaultName = 'push:subscribe';

    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    protected function configure()
    {
        $this->setDescription('Подписывает на топики с учётом домена, часового пояса и отступа (scheduled_at_offset). По умолчанию выбирает токены созданные за последний час')
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
                'From what date of creation select tokens (Y-m-d ex. 2024-01-01 00:00:00)',
                (new \DateTimeImmutable('now'))->modify('-1 hours')->format('Y-m-d H:i:s')
            )
            ->addOption(
                'dry_run',
                't',
                InputOption::VALUE_OPTIONAL,
                'Dry run show how much tokens will be subscribed, but not send request to Firebase',
                false
            );
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format('Y-m-d H:i:s');

        if (count($input->getOption('domains')) === 0) {
            $domains = $this->getDistinctFcmField('domain');
        } else {
            $domains = $input->getOption('domains');
        }

        $fromDate = $input->getOption('from_date');

        $this->logger()->info($this->colorizeText("subscribing..."));
        $offset = 0;
        $tokensMap = [];
        $sql0 = 'SELECT token, domain, tz_offset, scheduled_at_offset
                 FROM `fcm_token`
                 WHERE `created_at` > :dateTime  AND `created_at` <= \''. $now . '\' AND `unsub` = 0  
                 ORDER BY `created_at`
                 LIMIT 10000 OFFSET :offset';

        $sql1 = 'UPDATE `fcm_token` SET updated_at = NOW()
                 WHERE token IN (:tokensPlch)';
        while ($offset >= 0) {
            $this->logger()->info("Selecting with offset " . $offset );
            $sth = $this->pdo()->prepare($sql0);
            $sth->bindParam(':dateTime', $fromDate);
            $sth->bindParam(':offset', $offset, PDO::PARAM_INT);

            $sth->execute();
            $tokens = $sth->fetchAll(PDO::FETCH_ASSOC);
            if (\count($tokens) === 0) {
                $offset = -1;
                continue;
            }

            $tokensMap = [];
            foreach ($tokens as $tokenData) {
                if (!isset($tokensMap[$tokenData['domain']])) {
                    $tokensMap[$tokenData['domain']] = [];
                }
                if (!isset($tokensMap[$tokenData['domain']][$tokenData['tz_offset']])) {
                    $tokensMap[$tokenData['domain']][$tokenData['tz_offset']] = [];
                }
                if (!isset($tokensMap[$tokenData['domain']][$tokenData['tz_offset']][$tokenData['scheduled_at_offset']])) {
                    $tokensMap[$tokenData['domain']][$tokenData['tz_offset']][$tokenData['scheduled_at_offset']] = [];
                }
                $tokensMap[$tokenData['domain']][$tokenData['tz_offset']][$tokenData['scheduled_at_offset']][] = $tokenData['token'];
            }

            $offset += \count($tokens);
        }

        foreach ($tokensMap as $domain => $tzOffsets) {
            foreach ($tzOffsets as $tzOffset => $shards) {
                foreach ($shards as $shard => $tokens) {
                    $topic = $this->topicName($tzOffset, $shard, $domain);
                    $this->logger()->info("subs", ['topic' => $topic, 'tokens_cnt' => count($tokens)]);

                    while (\count($tokens) > 0) {
                        $tkns = array_splice($tokens, 0, 1000);
                        if (count($tkns) === 0) {
                            break;
                        }
                        if ($input->getOption('dry_run')) {
                            continue;
                        }
                        $this->logger()->info($this->colorizeText($topic));
                        $res = $this->firebase->subscribeTo($topic, $tkns);
                        $this->logger()->info($this->colorizeText(json_encode($res)));

                        $inQuery = str_repeat('?, ', count($tkns) - 1) . '?';
                        $sth = $this->pdo()->prepare(str_replace(':tokensPlch', $inQuery, $sql1));
                        $sth->execute($tkns);

                        $this->logger()->info("updated", ['topic' => $topic, 'tkns_cnt' => count($tkns), 'updated_tkns' => $sth->rowCount()]);

                    }
                }
            }
        }
        $this->logger()->info("Done push:subscribe");
        return 0;
    }
}
