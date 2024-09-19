<?php
declare(strict_types=1);

namespace Src\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity, ORM\Table(name: 'fcm_token_legacy')]
class FcmTokenLegacy extends BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 256)]
    protected $token;

    #[ORM\Column(type: 'string', length: 64)]
    protected $cookie;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected $createdAt;

    #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected $updatedAt;

    #[ORM\Column(type: 'string', length: 64)]
    protected $domain;

    #[ORM\Column(type: 'integer')]
    protected $newsId;

    #[ORM\Column(type: 'integer')]
    protected $newsCategory;

    #[ORM\Column(type: 'string', length: 4)]
    protected $device;

    #[ORM\Column(type: 'string', length: 2)]
    protected $geo;

    #[ORM\Column(type: 'smallint', options: ['default' => -180])]
    protected $timezoneOffset;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    protected $shardKey;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    protected $timezoneChanged;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    protected $tokenChanged;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    protected $unsubbed;
}