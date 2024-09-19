<?php
declare(strict_types=1);

namespace Console\Command;

use Doctrine\ORM\EntityManager;
use OpenTelemetry\API\Trace\TracerInterface;
use Prometheus\Histogram;
use Prometheus\RegistryInterface;
use Psr\Log\LoggerInterface;
use Redis;
use Src\Settings\SettingsInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;


abstract class BaseCommand extends Command
{
    const HISTOGRAM_TIME_PRECISION = 3;
    const COLOR_DEFAULT = "\e[39m";
    const COLOR_BLACK = "\e[30m";
    const COLOR_RED = "\e[31m";
    const COLOR_GREEN = "\e[32m";
    const COLOR_YELLOW = "\e[33m";
    const COLOR_BLUE = "\e[34m";
    const COLOR_LIGHT_CYAN = "\e[96m";

    const NAMESPACE = 'srv_push_cli';

    private const METRICS_HELP_TEXT = 'execution time in sec';
    private const METRIC_NAME = 'execution_time';
    private const METRIC_LABEL_NAME = 'cmd';
    private const METRIC_BUCKETS = [1, 5, 10, 20, 40, 80, 160, 320, 640];

    protected LoggerInterface $logger;
    protected \PDO $pdo;
    protected TracerInterface $tracer;
    protected $container;

    private $startTime;
    private $finishTime;

    protected Histogram $histogram;
    protected static $defaultName = 'example:command';

    public function __construct($container, string $name = null)
    {
        $this->container = $container;
        $registry = $this->container->get(\Prometheus\CollectorRegistry::class);
        $this->histogram = $registry->getOrRegisterHistogram(
            $this->getNamespace(), self::METRIC_NAME, self::METRICS_HELP_TEXT,
            [self::METRIC_LABEL_NAME],
            self::METRIC_BUCKETS
        );
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Example command');
    }

    protected function getNamespace()
    {
        return $this->getPreparedStringForRegistry(self::NAMESPACE);
    }

    protected function getPreparedStringForRegistry($string)
    {
        return strtolower(str_ireplace([' ', ':', '-'], '_', $string));
    }

    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputStatus = self::SUCCESS;
        $span = $this->container->get(TracerInterface::class)
            ->spanBuilder('#SRV-Push:command:exec')
            ->startSpan();
        $this->startTime = microtime(true);
        $startDateTime = date('Y-m-d H:i:s', (int)round($this->startTime));

        $span->addEvent('execution-start', [
            'command-name' => $this::$defaultName,
            'arguments' => $input->getArguments(),
            'start-time' => $startDateTime
        ]);

        try {
            $outputStatus = $this->executeCommand($input, $output);
        } catch (Throwable $exception) {
            $outputStatus = self::FAILURE;

            $message = "Error occurred in [" . $exception->getFile() . "] at line [" . $exception->getLine() . "]: [" . $exception->getMessage() . "]\n\n" .
                $exception->getTraceAsString();
            $span->addEvent('execution-error', ['message' => '#SRV-Push iav-cli exception\n\n' . $message]);
            $this->logger()->error($message);

        } finally {

            $this->finishTime = microtime(true);
            $finishTime = date('Y-m-d H:i:s', (int)round($this->finishTime));
            $executionTime = round($this->finishTime - $this->startTime, self::HISTOGRAM_TIME_PRECISION);

            $this->histogram->observe($executionTime, [
                self::METRIC_LABEL_NAME => $this->getPreparedStringForRegistry($this::$defaultName)
            ]);

            $span->addEvent('execution-finish ', [
                'finish-time' => $finishTime,
                'execution-time' => $executionTime
            ]);

            $span->end();
            return $outputStatus;
        }
    }

    protected function getTimeOutputFormat($timeInSeconds)
    {
        $format = 's сек.';
        if ($timeInSeconds > 60) {
            $format = 'i мин, ' . $format;
        }
        if ($timeInSeconds > 3600) {
            $format = 'H ч, ' . $format;
        }
        if ($timeInSeconds > 86400) {
            $format = 'd дн. ' . $format;
        }
        return $format;
    }

    abstract protected function executeCommand(InputInterface $input, OutputInterface $output);

    protected function getSettings($settingsName)
    {
        return $this->container->get(SettingsInterface::class)->get($settingsName);
    }

    protected function pdo(): \PDO
    {
        return $this->container->get(\PDO::class);
    }

    protected function tracer(): TracerInterface
    {
        return $this->container->get(TracerInterface::class);
    }

    protected function logger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }

    protected function redis(): Redis
    {
        return $this->container->get(Redis::class);
    }

    protected function getEntityManager(): EntityManager
    {
        return $this->container->get(EntityManager::class);
    }

    protected function colorizeText($text, $color = self::COLOR_LIGHT_CYAN, $colorAfter = self::COLOR_DEFAULT)
    {
        return $color . $text . $colorAfter;
    }

    protected function getPrometheusCollectorRegistry()
    {
        return $this->container->get(RegistryInterface::class);
    }
}