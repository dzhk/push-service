<?php
declare(strict_types=1);

namespace Api\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DomainsApiController extends BaseApiController
{
    public function loadDomains(
        ServerRequestInterface $request,
        ResponseInterface      $response,
    ): ResponseInterface
    {
        $data = $request->getParsedBody();
        // Validate the input data
        $constraints = new Assert\Collection([
            'domains' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\Collection([
                        'domain' => new Assert\Required([
                            new Assert\Hostname(),
                            new Assert\Length([
                                'min' => 1,
                                'max' => 512,
                            ])
                        ]),
                        'widget_id' => new Assert\Required([
                            new Assert\NotBlank(),
                            new Assert\Type('string'),
                            new Assert\Length([
                                'min' => 1,
                                'max' => 16,
                            ])
                        ]),
                        'partner_id' => new Assert\Required([
                            new Assert\NotBlank(),
                            new Assert\Type('integer'),
                            new Assert\GreaterThanOrEqual(1),
                        ]),
                        'localization' => new Assert\Required([
                            new Assert\Locale([
                                'canonicalize' => false,
                            ]),
                        ]),
                    ]),
                ]),
            ])
        ]);

        $this->validate($constraints, $data, $response);
        // У doсtrine тут с insert ignore не очень судя по всему
        $sth = $this->pdo()->prepare('
            INSERT IGNORE `domain` (`domain`, `rtb_widget_id`, `partner_id`, `localization`)
                 VALUES (:domain, :widget_id, :partner_id, :localization)
ON DUPLICATE KEY UPDATE `rtb_widget_id` = VALUES(`rtb_widget_id`),
                        `partner_id` = VALUES(`partner_id`),
                        `localization` = VALUES(`localization`)
        ');
        foreach ($data['domains'] as $domain) {
            $sth->execute([
                ':domain' => $domain['domain'],
                ':widget_id' => $domain['widget_id'],
                ':partner_id' => $domain['partner_id'],
                ':localization' => $domain['localization'],
            ]);
        }
        $response
            ->getBody()
            ->write(json_encode(['description' => 'it`s ok']));

        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }

}