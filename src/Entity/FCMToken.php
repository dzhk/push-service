<?php
declare(strict_types=1);

namespace Src\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;


#[Entity, Table(name: 'fcm_token')]
class FCMToken extends BaseEntity
{
    #[Id, Column(type: 'string', length: 255)]
    protected $token;

    #[Column(name: 'user_id', type: 'string', length: 50)]
    protected $userId;

    #[Column(type: 'integer', options: ['unsigned' => true])]
    protected $partnerId;

    #[Column(type: 'string', length: 1000)]
    protected $page;

    #[Column(name: 'device_type', type: 'integer', options: ['unsigned' => true])]
    protected $deviceType;

    #[Column(type: 'string', length: 20)]
    protected $browser;

    #[Column(type: 'string', length: 20)]
    protected $OS;

    #[Column(type: 'string', length: 2)]
    protected $country;

    #[Column(name: 'tz_offset', type: 'smallint')]
    protected $tzOffset;

    #[Column(name: 'utm_source', type: 'string', length: 255)]
    protected $utmSource;

    #[Column(name: 'utm_campaign', type: 'string', length: 255)]
    protected $utmCampaign;

    #[Column(name: 'utm_term', type: 'string', length: 255)]
    protected $utmTerm;

    #[Column(name: 'utm_content', type: 'string', length: 255)]
    protected $utmContent;

    #[Column(name: 'utm_medium', type: 'string', length: 255)]
    protected $utmMedium;

    #[Column(name: 'click_id', type: 'string', length: 255)]
    protected $clickid;

    #[Column(name: 'ab_test', type: 'string', length: 255)]
    protected $abTest;

    #[Column(name: 'user_agent', type: 'string', length: 512)]
    protected $userAgent;

    #[Column(name: 'ip_v4', type: 'integer', options: ['unsigned' => true])]
    protected $ipV4;

    #[Column(name: 'ip_v6', type: 'binary', length: 16)]
    protected $ipV6;

    #[Column(name: 'scheduled_at_offset', type: 'integer', options: ['unsigned' => false, 'default' => 0])]
    protected $shardKey;

    #[Column(name: 'timezone_changed', type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    protected $timezoneChanged;

    #[Column(name: 'token_changed', type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    protected $tokenChanged;

    #[Column(type: 'integer', options: ['unsigned' => true, 'default' => 0])]
    protected $unsub;

    #[Column(name: 'created_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected $createdAt;

    #[Column(name: 'updated_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    protected $updatedAt;
}