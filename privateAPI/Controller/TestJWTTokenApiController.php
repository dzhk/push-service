<?php
declare(strict_types=1);

namespace Api\Controller;

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TestJWTTokenApiController extends BaseApiController
{
    public function generateToken(
        ServerRequestInterface $request,
        ResponseInterface      $response,
                               $args
    ): ResponseInterface
    {
        $this->logger()->info('get token', ['asdfadsf' => 'dddd']);
        $settings = $this->getSettings('jwt');
        $expire = (new \DateTime("now"))
            ->modify("+1 hour")
            ->format("Y-m-d H:i:s");


        $token = JWT::encode(["expired_at" => $expire], $settings['private_key'], $settings['algorithm']);
        $response
            ->getBody()
            ->write($token);

        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus(201);

    }

    public function checkAuth(
        ServerRequestInterface $request,
        ResponseInterface      $response,
                               $args
    ): ResponseInterface
    {
        $response
            ->getBody()
            ->write(json_encode($request->getAttribute('user')));

        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus(201);

    }
}