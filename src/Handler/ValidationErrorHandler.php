<?php
declare(strict_types=1);

namespace Src\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\ErrorHandlerInterface;
use Slim\Psr7\Response;
use Throwable;

class ValidationErrorHandler implements ErrorHandlerInterface
{
    public function __invoke(
        ServerRequestInterface $request,
        Throwable              $exception,
        bool                   $displayErrorDetails,
        bool                   $logErrors,
        bool                   $logErrorDetails): ResponseInterface
    {

        $violations = $exception->getViolations();
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        $response = new Response();
        $response->getBody()->write(json_encode(['errors' => $errors]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');

    }
}