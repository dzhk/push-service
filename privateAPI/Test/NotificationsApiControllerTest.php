<?php

namespace Api\Test;

use PHPUnit\Framework\TestCase;

class NotificationsApiControllerTest extends TestCase
{
    use Trait\AppTestTrait;

    /**
     * @var int
     */
    protected $notificationId;

    public function testWithoutAuth()
    {
        $request = $this->createJsonRequest('GET', '/push/notifications', []);

        $response = $this->app->handle($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testCRUD()
    {
        $em = $this->getEntityManager();

        $em->getConnection()->beginTransaction();

        $addResponse = $this->addNotification();
        $this->assertEquals(201, $addResponse->getStatusCode());

        $getResponse = $this->getNotifications();
        $this->assertEquals(200, $getResponse->getStatusCode());
        $notificationId = json_decode($getResponse->getBody())->notifications[0]->id;
        $this->assertIsInt($notificationId);

        $deleteResponse = $this->deleteNotification($notificationId);
        $this->assertEquals(200, $deleteResponse->getStatusCode());

        $em->getConnection()->rollBack();
    }

    private function getNotifications()
    {
        $token = $this->getAuthToken();
        $data = [
            'pagination' => [
                'current_page' => 1,
                'items_per_page' => 1
            ]
        ];

        $request = $this->createJsonRequest(
            'GET',
            '/push/notifications',
            $data,
            ['Authorization' => 'Bearer ' . $token]
        );

        $response = $this->app->handle($request);

        return $response;
    }

    private function deleteNotification($notificationId)
    {
        $token = $this->getAuthToken();

        $request = $this->createJsonRequest(
            'DELETE',
            '/push/notifications/' . $notificationId,
            [],
            ['Authorization' => 'Bearer ' . $token]
        );

        $response = $this->app->handle($request);

        return $response;
    }

    private function addNotification()
    {
        $token = $this->getAuthToken();
        $data = [
            'notifications' => [
                $this->getCorrectNotificationData()
            ]
        ];

        $request = $this->createJsonRequest(
            'POST',
            '/push/notifications',
            $data,
            ['Authorization' => 'Bearer ' . $token]
        );

        $response = $this->app->handle($request);

        return $response;
    }

    private function getCorrectNotificationData()
    {
        return [
            'title' => 'Test Notification Autotest',
            'description' => 'Test description Autotest',
            'content_id' => 8230,
            'img' => 'https://static.local/images/external/12/89738.300032.500x300.1473549145.jpg',
            'link' => 'http://example.com',
            'localization' => 'ru',
            'scheduled_at' => date('Y-m-d H:i:s')
        ];
    }
}