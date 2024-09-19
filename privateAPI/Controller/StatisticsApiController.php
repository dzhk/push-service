<?php
declare(strict_types=1);

namespace Api\Controller;

use Src\Entity\Statistic10min;
use Src\Entity\StatisticDaily;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class StatisticsApiController extends BaseApiController
{
    const DEFAULT_PAGINATION = [
        'items_per_page' => 10,
        'current_page' => 1
    ];

    const STATISTIC_TYPE_10MIN = '10min';
    const STATISTIC_TYPE_DAILY = 'daily';
    const STATISTIC_TYPE_DEFAULT = self::STATISTIC_TYPE_DAILY;

    const STATISTIC_ENTITY_CLASSES = [
        self::STATISTIC_TYPE_10MIN => Statistic10min::class,
        self::STATISTIC_TYPE_DAILY => StatisticDaily::class,
    ];

    public function getStatistic(
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
                        new Assert\Range(['min' => 1, 'max' => 1000])
                    ]),
                ])
            ]),
            'statistic_type' => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Choice([self::STATISTIC_TYPE_10MIN, self::STATISTIC_TYPE_DAILY])
            ])
        ]);

        $this->validate($constraints, $data, $response);

        if (!isset($data['statistic_type'])) {
            $data['statistic_type'] = self::STATISTIC_TYPE_DEFAULT;
        }

        if (!isset($data['pagination'])) {
            $data['pagination'] = self::DEFAULT_PAGINATION;
        }

        $result = [
            'pagination' => ['total' => 0, 'current_page' => $data['pagination']['current_page']],
            'statistic' => [],
            'statistic_type' => $data['statistic_type']
        ];

        $statisticClass = self::STATISTIC_ENTITY_CLASSES[$data['statistic_type']];
        $entityManager = $this->entityManager();
        $queryBuilder = $entityManager
            ->createQueryBuilder()
            ->select('COUNT(1) as cnt')
            ->from($statisticClass, 's')
            ->orderBy('s.dateTime', 'DESC');
        // TODO: ограничение по времени не нужно, тк стата удаляется? Но наверное стоит добавить фильтры по дням, неделям

        $result['pagination']['total'] = (int)$queryBuilder->getQuery()->getSingleScalarResult();

        $queryBuilder->setFirstResult($data['pagination']['items_per_page'] * ($data['pagination']['current_page'] - 1));
        $queryBuilder->setMaxResults($data['pagination']['items_per_page']);
        $result['statistic'] = $queryBuilder->getQuery()->getResult();

        $response->withStatus(200)
            ->withHeader('Content-Type', 'application/json')
            ->getBody()
            ->write(json_encode($result));

        return $response;
    }
}