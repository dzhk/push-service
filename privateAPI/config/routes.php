<?php
declare(strict_types=1);

use Src\Middleware\JwtAuth;
use Slim\App;
use Api\Controller;


return function (App $app) {
    $app->group('privateApi', function () use ($app) {
        $app->post('/push/notifications', [Controller\NotificationsApiController::class, 'addNotifications'])->setName('addNotifications');
        $app->get('/push/notifications', [Controller\NotificationsApiController::class, 'getNotifications'])->setName('getNotifications');
        $app->delete('/push/notifications/{id}', [Controller\NotificationsApiController::class, 'deleteNotification'])->setName('deleteNotification');
        $app->get('/push/statistic', [Controller\StatisticsApiController::class, 'getStatistic'])
            ->setName('getStatistic');
        $app->post('/push/domains', [Controller\DomainsApiController::class, 'loadDomains'])->setName('loadDomains');
    })->add(JwtAuth::class)->add(\Src\Middleware\MetricsMiddleware::class);

    $app->group('publicMethods', function () use ($app) {
        $app->get('/metrics', [Controller\MetricsController::class, 'metrics'])->setName('getMetrics');
    });

    $app->group('TEEEEEEEST', function () use ($app) {
        $app->get('/get-token', [Controller\TestJWTTokenApiController::class, 'generateToken']);
        $app->post('/test/send', [Controller\TestFCMController::class, 'send']);
        $app->get('/check-auth', [Controller\TestJWTTokenApiController::class, 'checkAuth'])
            ->add(JwtAuth::class);
    });
};