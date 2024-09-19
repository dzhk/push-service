<?php
declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\HttpClient;
use Src\DBConnectors\RedisPush;
use Src\Encoder\UrlOpenSslEncoder;
use Src\Formatter\IAJsonLoggerFormatter;
use Src\Formatter\LinkPushTailFormatter;
use Src\Handler\ValidationErrorHandler;
use Src\Logger\IALogger;
use Src\Service\FirebaseAPIService;
use Src\Service\iAvProducerService;
use Src\Settings\Settings;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Monolog\Handler\StreamHandler;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use Prometheus\RenderTextFormat;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;
use Monolog\Logger;
use Src\Settings\SettingsInterface;
use Doctrine\DBAL\DriverManager;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;


return [
    SettingsInterface::class => function () {
        return new Settings(require APP_DIR . '/config/settings.php');
    },

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    PDO::class => function (ContainerInterface $container): PDO {
        $settings = $container->get(SettingsInterface::class);
        $dbSettings = $settings->get('db');

        $host = $dbSettings['host'];
        $dbname = $dbSettings['name'];
        $username = $dbSettings['user'];
        $password = $dbSettings['pass'];
        $charset = $dbSettings['charset'];
        // $flags = $dbSettings['flags'];
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        return new PDO($dsn, $username, $password);
    },
    Psr\Log\LoggerInterface::class => function (ContainerInterface $container): Psr\Log\LoggerInterface {
        $settings = $container->get(SettingsInterface::class);
        if ($settings->get('logger_type') === SettingsInterface::LOGGER_TYPE_OLTP) {
            // На данный момент OTEL_LOGS_EXPORTER: 'otlp' как-то криво работает TODO: проверять после обновления библиотеки open-telemetry/transport-grpc 1.0
            $loggerProvider = Globals::loggerProvider();
            $handler = new Handler(
                $loggerProvider,
                $settings->get('error')['log_level']
            );
        } else {
            $handler = new StreamHandler(
                'php://stdout',
                $settings->get('error')['log_level']
            );
        }
        $handler->setFormatter(new IAJsonLoggerFormatter());
        $serviceName = $settings->get('service_name') ?? 'srv-push';
        $logger = new IALogger($serviceName);
        $logger->pushHandler($handler);
        return $logger;
    },
    \Prometheus\CollectorRegistry::class => function (ContainerInterface $container): \Prometheus\CollectorRegistry {
        $settings = $container->get(SettingsInterface::class)->get('redis');
        \Prometheus\Storage\Redis::setDefaultOptions(
            [
                'host' => $settings['host'],
                'port' => $settings['port'],
                'password' => $settings['password'],
                'timeout' => $settings['timeout'],
                'read_timeout' => $settings['read_timeout'],
                'persistent_connections' => $settings['persistent_connections'],
            ]
        );
        return \Prometheus\CollectorRegistry::getDefault();
    },
    TracerInterface::class => function (ContainerInterface $container): TracerInterface {
        return OpenTelemetry\API\Globals::tracerProvider()->getTracer('name', 'version', 'schema.url', [/*attributes*/]);
    },
    MeterInterface::class => function (ContainerInterface $container) {
        return OpenTelemetry\API\Globals::meterProvider()->getMeter('name', 'version', 'schema.url', [/*attributes*/]);
    },
    EntityManager::class => function (ContainerInterface $container): EntityManager {
        $settings = $container->get(SettingsInterface::class);
        $settingsDB = $settings->get('db');
        $settingsDoctrine = $settings->get('doctrine');
        $paths = [$settingsDoctrine['entity_path']];
        #devMode - оставляем в true иначе долбится в кеш сервисы типа редиса на текущем сервере ( 127.0.0.1 )
        $isDevMode = true || $settingsDoctrine['is_dev_mode'];

        $dbParams = [
            'driver' => $settingsDB['driver'],
            'user' => $settingsDB['user'],
            'password' => $settingsDB['pass'],
            'dbname' => $settingsDB['name'],
            'host' => $settingsDB['host'],
        ];

        $config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode);
        $connection = DriverManager::getConnection($dbParams, $config);
        return new EntityManager($connection, $config);
    },
    ErrorMiddleware::class => function (ContainerInterface $container) {
        $app = $container->get(App::class);
        $settings = $container->get(SettingsInterface::class)->get('error');

        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$settings['display_error_details'],
            (bool)$settings['log_errors'],
            (bool)$settings['log_error_details']
        );

        $errorMiddleware->setErrorHandler(ValidationFailedException::class, ValidationErrorHandler::class);
        return $errorMiddleware;
    },
    RenderTextFormat::class => function (ContainerInterface $container) {
        return new RenderTextFormat();
    },
    ValidatorInterface::class => function (ContainerInterface $container) {
        return Validation::createValidator();
    },
    Redis::class => function (ContainerInterface $container) {
        $settings = $container->get(SettingsInterface::class)->get('redis');
        // TODO: посмотреть все ли важные опции применены
        $redis = new Redis();
        $redis->connect(
            $settings['host'], $settings['port'],
            $settings['timeout'], null,
            0, $settings['read_timeout']
        );
        $redis->auth($settings['password']);
        $redis->select($settings['database']);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
        return $redis;
    },
    RedisPush::class => function (ContainerInterface $container) {
        $settings = $container->get(SettingsInterface::class)->get('redis_queue');
        // TODO: посмотреть все ли важные опции применены
        $redis = new RedisPush();
        $redis->connect(
            $settings['host'], $settings['port'],
            $settings['timeout'], null,
            0, $settings['read_timeout']
        );
        $redis->auth($settings['password']);
        $redis->select($settings['database']);
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
        return $redis;
    },
    LinkPushTailFormatter::class => function (ContainerInterface $container) {
        $settings = $container->get(SettingsInterface::class)->get('url_open_ssl_encoder');
        return new LinkPushTailFormatter(
            new UrlOpenSslEncoder($settings['key'], $settings['algorithm'])
        );
    },
    FirebaseAPIService::class => static function (ContainerInterface $container) {
        $settings = $container->get(SettingsInterface::class)->get('firebase');
        return new FirebaseAPIService($settings);
    },
    iAvProducerService::class => static function (ContainerInterface $container) {
        $logger = $container->get(LoggerInterface::class);
        $producerUrl = $container->get(SettingsInterface::class)->get('url_producer');
        $client = HttpClient::create();

        if (false) { // make true for mocking
            $callback = function ($method, $url, $options): MockResponse {
                return new MockResponse('ok', ['http_code' => 202]);
            };

            $client = new MockHttpClient($callback);
        }

        return new iAvProducerService($producerUrl, $client, $logger);
    },
];