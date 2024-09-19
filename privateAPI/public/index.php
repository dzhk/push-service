<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';

use DI\ContainerBuilder;
use Symfony\Component\Dotenv\Dotenv;
use Slim\App;

chdir(dirname(__DIR__));
date_default_timezone_set("Europe/Moscow");

if (file_exists(APP_DIR . '/.env')) {
    (new Dotenv())->overload(APP_DIR . '/.env');
}

$containerBuilder = new ContainerBuilder();

// Set up settings
$containerBuilder->addDefinitions(__DIR__ . '/../config/dependencies.php');
$container = $containerBuilder->build();

$app = $container->get(App::class);

// дополнительная ручная трассировка
$tracer = \OpenTelemetry\API\Globals::tracerProvider()->getTracer('newne');
$meter = \OpenTelemetry\API\Globals::meterProvider()->getMeter('bew');

//$app->get('/rolldice', function (Request $request, Response $response) use ($tracer, $container) {
//    $span = $tracer
//        ->spanBuilder('manual-newne')
//        ->startSpan();
//    $result = random_int(1, 6);
//    $container->get(\Psr\Log\LoggerInterface::class)->info('ttttttttttter');
//    $response->getBody()->write(strval($result));
//    $span
//        ->addEvent('rolled dice', ['result' => $result])
//        ->end();
//    return $response;
//});


//$app->get('/prom', function (Request $request, Response $response, $args) use ($app) {
//    $registry = $this->get('prometheusRegistry');
//    $counter = $registry->getOrRegisterCounter('test', 'some_counter', 'it increases', ['type']);
//    $counter->incBy(3, ['blue']);
//
//    $gauge = $registry->getOrRegisterGauge('test', 'some_gauge', 'it sets', ['type']);
//    $gauge->set(2.5, ['blue']);
//
//    $histogram = $registry->getOrRegisterHistogram('test', 'some_histogram', 'it observes', ['type'], [0.1, 1, 2, 3.5, 4, 5, 6, 7, 8, 9]);
//    $histogram->observe(3.5, ['blue']);
//
//    $summary = $registry->getOrRegisterSummary('test', 'some_summary', 'it observes a sliding window', ['type'], 84600, [0.01, 0.05, 0.5, 0.95, 0.99]);
//    $summary->observe(5, ['blue']);
//
//    $response->getBody()->write(print_r($registry->getMetricFamilySamples(), true));
//    return $response;
//});
// Run app
(require __DIR__ . '/../config/routes.php')($app);
(require __DIR__ . '/../config/middleware.php')($app, $container);
$app->run();
