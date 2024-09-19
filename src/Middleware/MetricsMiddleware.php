<?php
declare(strict_types=1);

namespace Src\Middleware;

use OpenTelemetry\API\Trace\TracerInterface;
use Prometheus\Histogram;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;
use Src\Settings\SettingsInterface;
use Throwable;

class MetricsMiddleware
{
    private const HISTOGRAM_TIME_PRECISION = 3;
    private const METRIC_BUCKETS = [.005, .01, .025, .05, .1, .25, .5, 1, 2.5, 5, 10];
    private const METRICS_HELP_TEXT = 'execution time in sec';
    private const METRIC_NAME = 'execution_time';
    private const METRIC_LABELS = [
        'method',
        'status',
    ];
    const NAMESPACE = 'srv_push_private_api';
    private $startTime;
    private $finishTime;
    private $statusCode;

    protected Histogram $histogram;
    protected ContainerInterface $container;
    protected static $defaultName = 'example:command';

    // TODO: make traits
    protected function getNamespace()
    {
        return $this->getPreparedStringForRegistry(self::NAMESPACE);
    }

    protected function getPreparedStringForRegistry($string)
    {
        return strtolower(str_ireplace([' ', ':', '-'], '_', $string));
    }

    protected function logger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }

    public function __construct(SettingsInterface $settings, \Psr\Container\ContainerInterface $container)
    {
        $this->container = $container;
        $this->statusCode = 200;
        $registry = $this->container->get(\Prometheus\CollectorRegistry::class);
        $this->histogram = $registry->getOrRegisterHistogram(
            self::NAMESPACE, self::METRIC_NAME, self::METRICS_HELP_TEXT,
            self::METRIC_LABELS,
            self::METRIC_BUCKETS
        );
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

    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
        $error = false;
        $this->logger()->info('IT IS NEEEEEEEEEEEEEEETRICS');
        $span = $this->container->get(TracerInterface::class)
            ->spanBuilder('#SRV-Push:privateApi:call')
            ->startSpan();
        $this->startTime = microtime(true);
        $startDateTime = date('Y-m-d H:i:s', (int)round($this->startTime));

        $route = $request->getAttribute('__route__');
        $controller = $route->getCallable()[0];
        $action = $route->getCallable()[1];

        $url = $request->getUri()->getPath();
        $method = $request->getMethod();
        $span->addEvent('controller-call', [
            'url' => $url,
            'method' => $method,
            'controller' => $controller,
            'action' => $action,
            'start-time' => $startDateTime
        ]);

        try {
            $response = $handler->handle($request); // Пропускаем обработку запроса через остальные Middleware и контроллер
        } catch (Throwable $exception) {
            $this->statusCode = $exception->getCode();
            $message = "Error occurred in [" . $exception->getFile() . "] at line [" . $exception->getLine() . "]: [" . $exception->getMessage() . "]\n\n" .
                $exception->getTraceAsString();
            $span->addEvent('execution-error', ['message' => '#SRV-Push ' . self::NAMESPACE . ' exception\n\n' . $message]);
            $this->logger()->debug($message);
        } finally {
            $this->finishTime = microtime(true);
            $finishTime = date('Y-m-d H:i:s', (int)round($this->finishTime));

            $executionTime = round($this->finishTime - $this->startTime, self::HISTOGRAM_TIME_PRECISION);

            $this->histogram->observe($executionTime, [
                'method' => ($method . '_' . $url),
                'status' =>  $this->statusCode
            ]);

            $span->addEvent('execution-finish ', [
                'finish-time' => $finishTime,
                'execution-time' => $executionTime
            ]);

            $span->end();
            if ($this->statusCode !== 200) {
                throw $exception;
            }
        }

        return $response;
    }
}