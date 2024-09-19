<?php
declare(strict_types=1);

namespace Src\Middleware;

use Src\Settings\SettingsInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Psr7\Response;

final class JwtAuth
{
    private array $settings;
    private \Psr\Log\LoggerInterface  $logger;
    private ResponseInterface $response;

    public function __construct(SettingsInterface $settings, \Psr\Log\LoggerInterface $logger)
    {
        $this->settings = $settings->get('jwt');
        $this->response = new Response();
        $this->logger = $logger;
    }

    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
//        $this->logger->info('IT IS JWT');
//        $request = $request->withAttribute('user', [
//            'user_id' => 1,
//            'roles' => ['megaadmin', 'notonlyguest', 'derhergot']
//        ]);
//        return $handler->handle($request);
        try {
            if ($request->hasHeader("Authorization")) {
                $header = $request->getHeader("Authorization");

                if (!empty($header)) {
                    $bearer = trim($header[0]);
                    preg_match("/Bearer\s(\S+)/", $bearer, $matches);
                    $token = $matches[1];

                    $key = new Key($this->settings['public_key'], $this->settings['algorithm']);
                    $dataToken = JWT::decode($token, $key);

                    $nowDateTime = (new \DateTime("now"))->format("Y-m-d H:i:s");

                    if ($dataToken->expired_at <= $nowDateTime) {
                        $this->response->getBody()->write(json_encode(['message' => 'Token expired']));
                        return $this->response->withStatus(401)->withHeader('Content-Type', 'application/json');
                    } else {
                        $request = $request->withAttribute('user', [
                            'user_id' => 1,
                            'roles' => ['megaadmin', 'notonlyguest', 'derhergot']
                        ]);
                    }
                }
            } else {
                $this->response->getBody()->write(json_encode(['message' => 'Authorization required']));
                return $this->response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        } catch (\Exception $e) {
            $this->response->getBody()->write(json_encode(['message' => $e->getMessage()]));
            return $this->response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
        return $handler->handle($request);
    }
}