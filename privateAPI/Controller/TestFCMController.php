<?php
declare(strict_types=1);

namespace Api\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Src\Service\FirebaseAPIService;

class TestFCMController extends BaseApiController
{
    public function send(
        ServerRequestInterface $request,
        ResponseInterface      $response,
                               $args
    ): ResponseInterface
    {
        $req = $request->getParsedBody();

        $firebase = $this->container->get(FirebaseAPIService::class);
        $responses = $firebase->sendToMany($req['registration_ids'], [
            'notification' => $req['notification'],
            'data' => $req['data'],
            'time_to_live' => $req['time_to_live'],
        ]);
        $result = [];
        foreach ($responses->getItems() as $res) {
            if ($res->isFailure()) {
                $result[] = $res->error()->getMessage();
            } else {
                $result[] = $res->result();
            }
        }

        $response->getBody()->write(json_encode($result));

        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus(200);

    }
}