<?php
declare(strict_types=1);

namespace Api\Controller;

use Src\Entity\Notification;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class NotificationsApiController extends BaseApiController
{
    const DEFAULT_PAGINATION = [
        'items_per_page' => 10,
        'current_page' => 1
    ];

    const NOTIFICATION_OUT_SCHEMA = [
        'id',
        'title',
        'description',
        'img',
        'link',
        'localization' => [
            'sql' => 'UPPER(localization)',
            'alias' => 'localization'
        ],
        'scheduledAt' => [
            'type' => 'date',
            'sql' => 'scheduledAt',
            'alias' => 'scheduled_at'
        ]
    ];

    public function addNotifications(
        ServerRequestInterface $request,
        ResponseInterface      $response,
    ): ResponseInterface
    {
        $data = $request->getParsedBody();

        // Validate the input data
        $constraints = new Assert\Collection([
            'notifications' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\Collection([
                        'content_id' => new Assert\Required([
                            new Assert\Type('integer'),
                            new Assert\GreaterThanOrEqual(1),
                        ]),
                        'img' => new Assert\Optional([
//                            new Assert\Url(),
                            new Assert\Length(['min' => 1, 'max' => 1000,])
                        ]),
                        'link' => new Assert\Optional([
//                            new Assert\Url(),
                            new Assert\Length(['min' => 1, 'max' => 1000,])
                        ]),
                        'title' => new Assert\Required([
                            new Assert\Type(['type' => 'string']),
                        ]),
                        'description' => new Assert\Required([
                            new Assert\Type(['type' => 'string']),
                            new Assert\Length(['min' => 1, 'max' => 250,])
                        ]),
//                        'scheduled_at' => new Assert\Required([
//                            new Assert\DateTime()
//                        ]),
                        'localization' => new Assert\Required([
                            new Assert\Locale([
                                'canonicalize' => false,
                            ]),
                        ]),
                    ])
                ]),
            ])
        ]);
        $this->validate($constraints, $data, $response);

        $newNotificationData = [];
        foreach ($data['notifications'] as $notificationItem) {
            if (isset($newNotificationData[$notificationItem['localization']])) {
                $newNotificationData[$notificationItem['localization']][] = $notificationItem;
            } else {
                $newNotificationData[$notificationItem['localization']] = [$notificationItem];
            }
        }


        $entityManager = $this->entityManager();
        $entityManager->getConnection()->beginTransaction();
        $notificationsAddedObj = [];
        foreach ($newNotificationData as $localization => $notifications) {
            $sql = "SELECT scheduled_at 
            FROM srv_push.notification 
            WHERE localization = :localization 
            ORDER BY scheduled_at DESC LIMIT 1";

            $sth = $this->pdo()->prepare($sql);
            $sth->bindParam('localization', $localization);
            $sth->execute();
            $lastScheduledNotification = $sth->fetchColumn();
            if ($lastScheduledNotification === false) {
                $lastScheduledNotification = (new \DateTime())->add(new \DateInterval('PT1H'))->format('Y-m-d H:00:00');
            }
            $dt = new \DateTime($lastScheduledNotification);
            $dt = (new \DateTimeImmutable($dt->format('Y-m-d H:00:00')));

            $i = 0;
            $batchSize = 50;
            try {
                foreach ($notifications as $j => $notificationItem) {
                    // don't schedule for nighttime.
                    if ((int)$dt->format('H') >= 17 || (int)$dt->format('H') <= 6) {
                        $dt = $dt->add(new \DateInterval('P1D'));
                        $dt = $dt->setTime(7, 0, 0);
                    } else {
                        $dt = $dt->add(new \DateInterval('PT1H'));
                    }

                    $notification = new Notification();
                    $notification->contentId = $notificationItem['content_id'];
                    $notification->localization = $notificationItem['localization'];
                    $notification->description = $notificationItem['description'];
                    $notification->title = $notificationItem['title'];
                    $notification->scheduledAt = $dt;
                    $notification->img = $notificationItem['img'] ?? '';
                    $notification->link = $notificationItem['link'] ?? '';
                    $notification->isDeleted = 0;
                    $entityManager->persist($notification);
                    $notificationsAddedObj[] = $notification;
                    if ($i > 0 && ($i++ % $batchSize) === 0) {
                        $entityManager->flush();
                        $entityManager->clear();
                    }
                }
                $entityManager->flush();
                $entityManager->clear();
            } catch (\Throwable $exception) {
                $entityManager->getConnection()->rollBack();
                $this->logger()->warning($exception->getMessage());
                $response
                    ->getBody()
                    ->write(json_encode(['description' => 'Ошибка при добавлении оповещений']));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }

        $notificationsResponse = array_map(static function ($item) {
            $notifyItem = [];
            foreach (self::NOTIFICATION_OUT_SCHEMA as $key => $value) {
                if (is_int($key)) {
                    $notifyItem[$value] = $item->$value;
                } else {
                    if (isset($value['type']) && $value['type'] === 'date') {
                        $notifyItem[$value['alias']] = $item->$key->format('Y-m-d H:i:s');
                    } else {
                        $notifyItem[$value['alias']] = $item->$key;
                    }
                }
            }
            return $notifyItem;
        }, $notificationsAddedObj);
        $entityManager->getConnection()->commit();
        $response
            ->getBody()
            ->write(json_encode([
                'description' => 'it`s ok',
                'notifications' => $notificationsResponse
            ]));
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
    }

    public function getNotifications(
        ServerRequestInterface $request,
        ResponseInterface      $response,
    ): ResponseInterface
    {
        $data = $request->getParsedBody();

        // Validate the input data
        $constraints = new Assert\Collection([
            'pagination' => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Collection([
                    'current_page' => new Assert\Required([
                        new Assert\Type('integer'),
                    ]),
                    'items_per_page' => new Assert\Required([
                        new Assert\Type('integer'),
                        new Assert\Range(['min' => 1, 'max' => 100])
                    ]),
                ])
            ]),
        ]);

        $this->validate($constraints, $data, $response);

        if (!isset($data['pagination'])) {
            $data['pagination'] = self::DEFAULT_PAGINATION;
        }

        $entityManager = $this->entityManager();
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('COUNT(1) as cnt')
            ->from(Notification::class, 'n')
            ->where('n.isDeleted = :is_deleted')
            ->setParameter('is_deleted', 0);

        $count = $queryBuilder->getQuery()->getSingleScalarResult();

        $result = [
            'pagination' => $data['pagination'],
            'notifications' => []
        ];
        $result['pagination']['total'] = $count;

        $offset = $data['pagination']['items_per_page'] * ($data['pagination']['current_page'] - 1);
        $shema = array_map(function ($key, $value) {
            if (is_int($key)) {
                return 'n.' . $value;
            } else {
                return str_replace($key, 'n.' . $key, $value['sql']) . ' as ' . $value['alias'];
            }
        }, array_keys(self::NOTIFICATION_OUT_SCHEMA), array_values(self::NOTIFICATION_OUT_SCHEMA));
        $queryBuilder
            ->select($shema)
            ->setFirstResult($offset)
            ->setMaxResults($data['pagination']['items_per_page']);

        $result['notifications'] = array_map(function ($item) {
            $item['scheduled_at'] = $item['scheduled_at']->format('Y-m-d H:i:s');
            return $item;
        }, $queryBuilder
            ->getQuery()
            ->getArrayResult());
        $response->getBody()->write(json_encode($result));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

    public function deleteNotification(
        ServerRequestInterface $request,
        ResponseInterface      $response,
                               $args
    ): ResponseInterface
    {
        if (((int)$args['id']) == $args['id']) {
            $args['id'] = (int)$args['id'];
        }
        $constraints = new Assert\Collection([
            'id' => new Assert\Required([
                new Assert\Type('integer'),
                new Assert\GreaterThanOrEqual(1)
            ]),
        ]);

        $this->validate($constraints, $args, $response);

        $entityManager = $this->entityManager();
        $queryBuilder = $entityManager->createQueryBuilder();
        $affectedRows = $queryBuilder->update(Notification::class, 'n')
            ->set('n.isDeleted', 1)
            ->where('n.id = :id')
            ->andWhere('n.isDeleted = :is_deleted')
            ->setParameter('id', $args['id'])
            ->setParameter('is_deleted', 0)
            ->getQuery()
            ->execute();

        if ($affectedRows > 0) {
            $response->getBody()->write(json_encode(['description' => 'Оповещение удалено']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(['description' => 'Оповещение с id = ' . $args['id'] . ' не существует или уже было удалено']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}