<?php
declare(strict_types=1);

namespace Console\Command;

use Src\Service\FirebaseAPIService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * nohup ./iav-cli tokens:check-exists -p e -s 2 -b 120 > check-exists-e2.txt &
 * nohup ./iav-cli tokens:check-exists -p e -s 1 -b 120 > check-exists-e1.txt &
 * nohup ./iav-cli tokens:check-exists -p f -s 2 -b 120 > check-exists-f2.txt &
 * nohup ./iav-cli tokens:check-exists -p f -s 1 -b 120 > check-exists-f1.txt &
 * nohup ./iav-cli tokens:check-exists -p c -s 2 -b 120 > check-exists-c2.txt &
 * nohup ./iav-cli tokens:check-exists -p c -s 1 -b 120 > check-exists-c1.txt &
 * nohup ./iav-cli tokens:check-exists -p d -s 2 -b 120 > check-exists-d2.txt &
 * nohup ./iav-cli tokens:check-exists -p d -s 1 -b 120 > check-exists-d1.txt &
 */
final class CheckTokensCommand extends BaseCommand
{
    const DEFAULT_BATCH_SIZE = 150;
    protected static $defaultName = 'tokens:check-exists';
    private $firebase;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        $settings = $this->getSettings('firebase');
        $this->firebase = new FirebaseAPIService($settings);
    }

    protected function configure()
    {
        $this->setDescription('Check tokens still exists in firebase')
            ->addOption(
                'token_prefix',
                'p',
                InputOption::VALUE_OPTIONAL,
                'First char of token (c, d, e, f) using in sql: token LIKE \'${p}%\'',
                ''
            )
            ->addOption(
                'batch_size',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Batch size of tokens array for validation, int value',
                self::DEFAULT_BATCH_SIZE
            )
            ->addOption(
                'split_in_use',
                's',
                InputOption::VALUE_OPTIONAL,
                'if isset then use this half of token for checking (limit offset logic). -s 1 - use first half of tokens -s 2 - use second half',
                0
            );
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output)
    {
        $like = '';
        if ($input->getOption('token_prefix') !== '') {
            $like = " token LIKE '" . $input->getOption('token_prefix') . "%' AND ";
        }
        $batchSize = (int)$input->getOption('batch_size');

        $sthCount = $this->pdo()->prepare("SELECT COUNT(*) 
            FROM `fcm_token` 
            WHERE $like `unsub` = 0 AND subscription <> ''");
        $sthCount->execute();
        $count = $sthCount->fetchColumn();

        $useSplit = (int)$input->getOption('split_in_use');

        $sql = "SELECT token 
            FROM `fcm_token` 
            WHERE $like `unsub` = 0 AND subscription <> ''
            ORDER BY token LIMIT :batchSize OFFSET :offset";
        $sth = $this->pdo()->prepare($sql);
        $this->logger()->info('Start checking', ['total' => $count]);

        if ($useSplit === 2) {
            $offset = $count - intdiv($count, 2);
            $maxOffset = $count;
        } elseif ($useSplit === 1) {
            $offset = 0;
            $maxOffset = $count - intdiv($count, 2);
        } else {
            $offset = 0;
            $maxOffset = $count;
        }
        $rowCount = 1;
        $batchIteration = 0;
        while ($rowCount && $offset < $maxOffset) {
            $sth->bindParam(':batchSize', $batchSize, \PDO::PARAM_INT);
            $offset += $batchSize;
            $sth->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $sth->execute();
            $rowCount = $sth->rowCount();
            $this->logger()->info('Iteration ' . $batchIteration, [
                'checked' => $batchIteration * $batchSize,
                'offset' => $offset,
                'batchSize' => $batchSize,
                'nextToCheck' => $rowCount
            ]);
            $tokens = $sth->fetchAll(\PDO::FETCH_COLUMN);
            if ($tokens === false || count($tokens) === 0) {
                $this->logger()->info('No tokens for checking');
                break;
            }

            $this->logger()->info('Iteration ' . $batchIteration . ' Tokens check  ' . count($tokens) . ' pcs');
            try {
                $res = $this->firebase->validateTokens($tokens);
                $this->logger()->info('Iteration ' . $batchIteration, [
                    'valid' => count($res['valid']),
                    'unknown' => count($res['unknown']),
                    'invalid' => count($res['invalid'])
                ]);
                if (isset($res['valid']) && count($res['valid']) > 0) {
                    $i = 1;
                    $params = [];
                    foreach ($res['valid'] as $validToken) {
                        $params[':token' . $i++] = $validToken;
                    }
                    $query = 'UPDATE `fcm_token` SET `updated_at` = NOW()
                        WHERE `token` IN (' . implode(',', array_keys($params)) . ')';
                    $sthUpdateValid = $this->pdo()->prepare($query);
                    $sthUpdateValid->execute($params);
                }

                if (isset($res['unknown']) || isset($res['invalid'])) {
                    $arrInvalid = array_merge($res['unknown'] ?? [], $res['invalid'] ?? []);

                    if (count($arrInvalid) > 0) {
                        $i = 1;
                        $params = [];
                        foreach ($arrInvalid as $validToken) {
                            $params[':token' . $i++] = $validToken;
                        }
                        $query = 'UPDATE `fcm_token` SET `unsub` = 1
                        WHERE `token` IN (' . implode(',', array_keys($params)) . ')';
                        $sthUpdateValid = $this->pdo()->prepare($query);
                        $sthUpdateValid->execute($params);
                    }
                }
            } catch (\Throwable $exception) {
                $this->logger()->info(
                    'Iteration ' . $batchIteration, [
                    'error' => $exception->getMessage(),
                ]);
            }

            $this->logger()->info('Checked.');
            $batchIteration++;
        }
        return 0;
    }
}
