<?php

namespace Src\Logger;

use DateTimeImmutable;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\Contrib\Logs\Monolog\Handler as OtelHandler;
use Psr\Log\LogLevel;
use Src\Formatter\IAJsonLoggerFormatter;

class IALogger extends Logger
{
    protected string $serviceName;

    public function __construct(
        string $serviceName = 'srv-push',
        $logLevel = LogLevel::INFO,
        $handlers = [] //['otel', 'php://stdout', 'filePath']
    ) {
        parent::__construct($serviceName);

        $loggerProvider = Globals::loggerProvider();
        $this->serviceName = $serviceName;

        foreach ($handlers as $settingsHandler) {
            if ($settingsHandler instanceof AbstractProcessingHandler) {
                $handler = $settingsHandler;
            } elseif ($settingsHandler === 'otel') {
                $handler = new OtelHandler(
                    $loggerProvider,
                    $logLevel
                );
            } else {
                $handler = new StreamHandler($settingsHandler, $logLevel);
            }
            $handler->setFormatter(new IAJsonLoggerFormatter());
            $this->pushHandler($handler);
        }
    }

    public function addRecord($level, $message, array $context = [], DateTimeImmutable|\Monolog\DateTimeImmutable $datetime = null): bool
    {
        $context['level'] = $level;
        $context['ts'] = microtime(true);
        $context['service'] = $this->serviceName;
        $context['msg'] = $message;

        if (isset($context['span']) && $context['span'] instanceof SpanInterface) {
            $span = $context['span'];
            $context['trace_id'] = $span->getContext()->getTraceId();
            $context['span_id'] = $span->getContext()->getSpanId();
            unset($context['span']); // Remove the span object from context
        }

        return parent::addRecord($level, $message, $context);
    }
}
