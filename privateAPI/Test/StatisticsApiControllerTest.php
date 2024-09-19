<?php

namespace Api\Test;
use PHPUnit\Framework\TestCase;

class StatisticsApiControllerTest extends TestCase
{
    use Trait\AppTestTrait;

    public function testGetStatisticWithRequiredParams()
    {
        $token = $this->getAuthToken();

        $request = $this->createJsonRequest('GET', '/push/statistic', [
            'statistic_type' => 'daily',
            'pagination' => [
                'current_page' => 1,
                'items_per_page' => 10
            ]
        ], ['Authorization' => 'Bearer ' . $token]);

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetStatisticWithoutAuth()
    {
        $request = $this->createJsonRequest('GET', '/push/statistic', [
            'statistic_type' => 'daily',
            'pagination' => [
                'current_page' => 1,
                'items_per_page' => 10
            ]
        ], []);

        $response = $this->app->handle($request);

            $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGetStatisticWithoutParams()
    {
        $token = $this->getAuthToken();
        $request = $this->createJsonRequest('GET', '/push/statistic',[],
            ['Authorization' => 'Bearer ' . $token]
        );

        $response = $this->app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }
}