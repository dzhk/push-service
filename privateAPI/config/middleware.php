<?php
declare(strict_types=1);

use Slim\App;
use Slim\Middleware\ErrorMiddleware;

return function (App $app, $container) {
    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    // $app->add(new StartSession());
    $app->add(ErrorMiddleware::class);
};