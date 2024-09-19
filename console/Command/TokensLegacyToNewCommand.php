<?php
declare(strict_types=1);

namespace Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class TokensLegacyToNewCommand extends BaseCommand
{
    const DEFAULT_BATCH_SIZE = 1000;

    public function __construct($container, string $name = null)
    {
        parent::__construct($container, $name);
        $this->addOption('batchSize', 'b', InputOption::VALUE_OPTIONAL, 'Размер выборки для вставки за одну итерацию', self::DEFAULT_BATCH_SIZE);
    }

    protected static $defaultName = 'tokens:legacy-to-new';

    protected function configure()
    {
        $this->setDescription('Перенос токенов из таблицы fcm_token_legacy в fcm_token');
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $this->logger()->info('Начинаем перенос токенов count() = 122000 (хардкод)');
        $batchSize = (int)$input->getOption('batchSize');
        $batchSize = $batchSize > 0 ? $batchSize : self::DEFAULT_BATCH_SIZE;

        $tpmQuery = "
        INSERT INTO `fcm_token` (token, user_id, partner_id, domain, page, device_type,
                         browser, OS, country, tz_offset,
                         utm_source, utm_campaign, utm_term, utm_content, utm_medium,
                         clickid, ab_test, user_agent,
                         ip_v4, ip_v6, scheduled_at_offset,
                         timezone_changed, token_changed,
                         unsub, created_at, updated_at
        )
        SELECT token, '' as user_id, 1 as partner_id, domain, '' AS page, IFNULL(dt.id, 0) AS device_type,
               '' AS browser, '' AS OS, geo AS coutry, timezone_offset as tz_offset,
               '' AS utm_source, '' AS utm_campaign, '' AS utm_term, '' AS utm_content, '' AS utm_medium,
               '' AS clickid, '' AS ab_test, '' AS user_agent,
               0 as ip_v4, 0 as ip_v6, FLOOR((RAND() * 59)) as scheduled_at_offset,
               timezone_changed, token_changed,
               unsubbed, created_at, '2024-02-13 17:00:00'
        FROM `fcm_token_legacy`
        LEFT JOIN `device_type` `dt` ON (`dt`.`name` = `fcm_token_legacy`.`device`)
        ORDER BY token
        LIMIT :limit OFFSET :offset
        ON DUPLICATE KEY UPDATE updated_at = '2024-02-13 17:00:00'";
        $sthFirstLoad = $this->pdo()->prepare($tpmQuery);
        $totalCnt = 122000;
        $offset = 0;
        while ($offset < $totalCnt) {

            $sthFirstLoad->bindParam(':limit', $batchSize, \PDO::PARAM_INT);
            $sthFirstLoad->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $sthFirstLoad->execute();

            $this->logger()->info('moved ' . $sthFirstLoad->rowCount() . ' tokens');
            $offset += $batchSize;
        }
        $this->logger()->info('Done');
        return 0;
    }
}