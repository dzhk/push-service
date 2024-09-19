<?php
declare(strict_types=1);

namespace Src\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class PrettyErrorMiddleware
{
    protected $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $whoops = new Run;
        $whoops->pushHandler(new PrettyPageHandler);
        $whoops->register();

        $response = $next($request, $response);

        return $response;
    }
}