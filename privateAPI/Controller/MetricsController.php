<?php
declare(strict_types=1);

namespace Api\Controller;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MetricsController
{
    private CollectorRegistry $registry;
    private RenderTextFormat $renderer;
    private ContainerInterface $container;

    public function __construct(
        ContainerInterface $container
    )
    {
        $this->container = $container;
        $this->registry = $container->get(CollectorRegistry::class);
        $this->renderer = $container->get(RenderTextFormat::class);
    }

    public function metrics(
        ServerRequestInterface $request,
        ResponseInterface      $response,
    )
    {
//        $redis = $this->container->get(\Redis::class);
//        $redis->flushall();
        $response
            ->getBody()
            ->write($this->renderer->render($this->registry->getMetricFamilySamples()));
        return $response->withHeader('Content-type', RenderTextFormat::MIME_TYPE);
    }
}