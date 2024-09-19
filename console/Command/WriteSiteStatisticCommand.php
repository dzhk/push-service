<?php
declare(strict_types=1);

namespace Console\Command;

use Psr\Log\LoggerInterface;
use Redis;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class WriteSiteStatisticCommand extends BaseCommand
{
    private const KEY_PREFIX_STAT = 'e:';
    private const SEPARATOR = '·';
    protected static $defaultName = 'write:site-statistic';
    protected LoggerInterface $logger;
    protected Redis $redis;
    protected \PDO $pdo;

    public function __construct($pdo, $redis, $logger, ...$args)
    {
        parent::__construct(...$args);
        $this->redis = $redis;
        $this->logger = $logger;
        $this->pdo = $pdo;
    }

    protected function configure()
    {
        $this
            ->setDescription('Move site statistic from redis to MariaDB')
            ->addOption(
                'prev_intervals',
                'p',
                InputOption::VALUE_REQUIRED,
                'How many time intervals scanned from Redis. Intervals to time: p*10min',
                6
            )
            ->addOption(
                'to_datetime',
                't',
                InputOption::VALUE_REQUIRED,
                'Until what time should records in Redis be scanned: time();',
                time()
            );
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $toTimeStamp = time();
        $toDateTime = $input->getOption('to_datetime');
        if (is_string($toDateTime)) {
            $dt = \DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $toDateTime);
            if ($dt !== false && $dt::getLastErrors() === false) {
                $toTimeStamp = $dt->getTimestamp();
            }
        }
        $now10minInterval = (int)($toTimeStamp / 600) * 600;
        for ($i = 1; $i <= $input->getOption('prev_intervals'); $i++) {
            $last10minIntervals[] = $now10minInterval - $i * 600;
        }

        $keys = [];
        foreach ($last10minIntervals as $last10minInterval) {
            $pattern = self::KEY_PREFIX_STAT . $last10minInterval . '·*';
            $this->logger->notice('scan information for ' . $pattern);
            $iterator = null;
            do {
                $response = $this->redis->scan($iterator, $pattern, 1000);
                if ($response !== false && \count($response) > 0) {
                    foreach ($response as $element) {
                        $keys[] = $element;
                    }
                }
            } while ($iterator > 0);
        }

        $this->logger->notice('keys for getting : ' . count($keys));
        $insertData = [];
        $insertDailyData = [];
        foreach ($keys as $key) {
            $stat = $this->redis->hGetAll($key);
            $attrs = explode(self::SEPARATOR, $key);
            $insertData[] = [
                'date_time' => date("Y-m-d H:i:s", (int)substr($attrs[0], 2)),
                'unique_key' => $this->createUniqueKey($attrs),
                'partner_id' => (int)$attrs[1],
                'domain' =>  mb_substr($attrs[2], 0, 40),
                'device_type' => (int)$attrs[3],
                'browser' => mb_substr($attrs[4], 0, 20),
                'OS' => mb_substr($attrs[5], 0, 20),
                'model' => mb_substr($attrs[6], 0, 40),
                'country' => $attrs[7],
                'tz_offset' => (int)$attrs[8],
                'utm_source' => mb_substr($attrs[9], 0, 255),
                'utm_campaign' => mb_substr($attrs[10], 0, 255),
                'utm_term' => mb_substr($attrs[11], 0, 255),
                'utm_content' => mb_substr($attrs[12], 0, 255),
                'ab_test' => mb_substr($attrs[13], 0, 255),
                'js_loads' => $stat['js_loads'] ?? 0,
                'confirm_requests' => $stat['confirm_requests'] ?? 0,
                'subs' => $stat['subs'] ?? 0,
                'closes' => $stat['closes'] ?? 0,
                'blocked' => $stat['blocked'] ?? 0,
                'unsubs' => $stat['unsubs'] ?? 0,
                'notification_delivered' => $stat['notification_delivered'] ?? 0,
                'notification_clicked' => $stat['notification_clicked'] ?? 0,
                'notification_closed' => $stat['notification_closed'] ?? 0,
                'income_by_cpc' => $stat['income_by_cpc'] ?? 0,
                'income_by_cpm' => $stat['income_by_cpm'] ?? 0,
                'income_by_cpa' => $stat['income_by_cpa'] ?? 0,
            ];
            $attrs[0] = date("Y-m-d 00:00:00", (int)substr($attrs[0], 2));
            $insertDailyData[] = [
                'date_time' => $attrs[0],
                'unique_key' => $this->createUniqueKey($attrs),
                'partner_id' => (int)$attrs[1],
                'domain' =>  mb_substr($attrs[2], 0, 40),
                'device_type' => (int)$attrs[3],
                'browser' => mb_substr($attrs[4], 0, 20),
                'OS' => mb_substr($attrs[5], 0, 20),
                'model' => mb_substr($attrs[6], 0, 40),
                'country' => $attrs[7],
                'tz_offset' => (int)$attrs[8],
                'utm_source' => mb_substr($attrs[9], 0, 255),
                'utm_campaign' => mb_substr($attrs[10], 0, 255),
                'utm_term' => mb_substr($attrs[11], 0, 255),
                'utm_content' => mb_substr($attrs[12], 0, 255),
                'ab_test' => mb_substr($attrs[13], 0, 255),
                'js_loads' => $stat['js_loads'] ?? 0,
                'confirm_requests' => $stat['confirm_requests'] ?? 0,
                'subs' => $stat['subs'] ?? 0,
                'closes' => $stat['closes'] ?? 0,
                'blocked' => $stat['blocked'] ?? 0,
                'unsubs' => $stat['unsubs'] ?? 0,
                'notification_delivered' => $stat['notification_delivered'] ?? 0,
                'notification_clicked' => $stat['notification_clicked'] ?? 0,
                'notification_closed' => $stat['notification_closed'] ?? 0,
                'income_by_cpc' => $stat['income_by_cpc'] ?? 0,
                'income_by_cpm' => $stat['income_by_cpm'] ?? 0,
                'income_by_cpa' => $stat['income_by_cpa'] ?? 0,
            ];
        }
        $this->logger->notice('keys for insert : ' . count($insertData));
        while (count($insertData) > 0) {
            $data = array_splice($insertData, 0, 5000);
            $sql = $this->insert('statistic_10min', $data, array_keys($data[0]));

            $res = $this->pdo->exec($sql);
            $this->logger->notice('inserted in statistic_10min rows: ' . count($data));
            if ($res === false) {
                throw new \RuntimeException($this->pdo->errorCode() . implode(', ', $this->pdo->errorInfo()));
            }
        }
        while (count($insertDailyData) > 0) {
            $data = array_splice($insertDailyData, 0, 5000);
            $sql = $this->insert('statistic_daily', $data, array_keys($data[0]));
            $sql .= ' ON DUPLICATE KEY UPDATE 
                `js_loads` = `js_loads` + VALUES(`js_loads`),
                `confirm_requests` = `confirm_requests` + VALUES(`confirm_requests`),
                `subs` = `subs` + VALUES(`subs`),
                `closes` = `closes` + VALUES(`closes`),
                `blocked` = `blocked` + VALUES(`blocked`),
                `unsubs` = `unsubs` + VALUES(`unsubs`),
                `notification_delivered` = `notification_delivered` + VALUES(`notification_delivered`),
                `notification_clicked` = `notification_clicked` + VALUES(`notification_clicked`),
                `notification_closed` = `notification_closed` + VALUES(`notification_closed`),
                `income_by_cpc` = `income_by_cpc` + VALUES(`income_by_cpc`),
                `income_by_cpm` = `income_by_cpm` + VALUES(`income_by_cpm`),
                `income_by_cpa` = `income_by_cpa` + VALUES(`income_by_cpa`)';
            $res = $this->pdo->exec($sql);
            $this->logger->notice('inserted in statistic_daily rows: ' . count($data));
            if ($res === false) {
                throw new \RuntimeException($this->pdo->errorCode() . implode(', ', $this->pdo->errorInfo()));
            }
        }

        foreach ($keys as $key) {
            $this->redis->del($key);
        }

        $this->logger->notice('written keys: ' . count($keys));
        return 0;
    }

    private function createUniqueKey(array $attrs)
    {
        return md5(mb_strtolower(
            $attrs[0] . " " .
            $attrs[1] . " " .
            $attrs[2] . " " .
            $attrs[3] . " " .
            $attrs[4] . " " .
            $attrs[5] . " " .
            $attrs[6] . " " .
            $attrs[7] . " " .
            $attrs[8] . " " .
            $attrs[9] . " " .
            $attrs[10] . " " .
            $attrs[11] . " " .
            $attrs[12] . " " .
            $attrs[13]
        ));
    }

    private function insert(string $table, array $values, array $columns = []): string
    {
        if (!str_contains($table, '`') && !str_contains($table, '.')) {
            $table = '`' . $table . '`';
        }
        $sql = 'INSERT INTO ' . $table;

        if (count($columns) !== 0) {
            $sql .= ' (`' . implode('`,`', $columns) . '`) ';
        }

        $sql .= ' VALUES ';

        foreach ($values as $row) {
            $sql .= ' (' . implode(',', $this->quoteValue($row)) . '), ';
        }
        return trim($sql, ', ');
    }

    private function quoteValue(array $values): array
    {
        $res = [];
        foreach ($values as $value) {
            if (is_float($value) || is_int($value)) {
                $res[] = $value;
                continue;
            }
            if (is_string($value)) {
                $res[] = '\'' . $value . '\'';
                continue;
            }
            if (is_null($value)) {
                $res[] = 'NULL';
            }
        }
        return $res;
    }
}