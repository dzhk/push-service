<?php
declare(strict_types=1);

namespace Src\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "statistic_10min")]
class Statistic10min extends BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(name: "date_time", type: "datetime")]
    protected $dateTime;

    #[ORM\Id]
    #[ORM\Column(name: "unique_key", type: "string", length: 40)]
    protected $uniqueKey;

    #[ORM\Column(name: "partner_id", type: "integer", options: ["unsigned" => true])]
    protected $partnerId;

    #[ORM\Column(type: "string", length: 20)]
    protected $domain;

    #[ORM\Column(name: "device_type", type: "integer", options: ["unsigned" => true])]
    protected $deviceType;

    #[ORM\Column(type: "string", length: 20)]
    protected $browser;

    #[ORM\Column(type: "string", length: 20)]
    protected $os;

    #[ORM\Column(type: "string", length: 20)]
    protected $model;

    #[ORM\Column(type: "string", length: 2)]
    protected $country;

    #[ORM\Column(name: "tz_offset", type: "smallint")]
    protected $tzOffset;

    #[ORM\Column(name: "utm_source", type: "string", length: 255)]
    protected $utmSource;

    #[ORM\Column(name: "utm_campaign", type: "string", length: 255)]
    protected $utmCampaign;

    #[ORM\Column(name: "utm_term", type: "string", length: 255)]
    protected $utmTerm;

    #[ORM\Column(name: "utm_content", type: "string", length: 255)]
    protected $utmContent;

    #[ORM\Column(name: "ab_test", type: "string", length: 255, options: ["default" => ""])]
    protected $abTest;

    #[ORM\Column(name: "js_load", type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $jsLoad;

    #[ORM\Column(name: "confirm_request", type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $confirmRequest;

    #[ORM\Column(type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $subs;

    #[ORM\Column(type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $closes;

    #[ORM\Column(type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $blocked;

    #[ORM\Column(type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $unsub;

    #[ORM\Column(name: "notification_delivered", type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $notificationDelivered;

    #[ORM\Column(name: "notification_clicks", type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $notificationClicks;

    #[ORM\Column(name: "notification_closes", type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $notificationCloses;

    #[ORM\Column(name: "income_by_cpc", type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $incomeByCpc;

    #[ORM\Column(name: "income_by_cpm", type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $incomeByCpm;

    #[ORM\Column(name: "income_by_cpa", type: "integer", options: ["unsigned" => true, "default" => 0])]
    protected $incomeByCpa;
}