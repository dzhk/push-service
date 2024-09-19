<?php
declare(strict_types=1);

namespace Src\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
#[ORM\Table(name: "domain")]
class Domain extends BaseEntity
{
    #[ORM\Id]
    #[ORM\Column(type: "string", length: 512)]
    protected string $domain;

    #[ORM\Column(name: 'rtb_widget_id', type: "string", length: 16)]
    protected string $rtbWidgetId;

    #[ORM\Column(name: 'partner_id', type: "integer", options: ["unsigned" => true])]
    protected int $partnerId;

    #[ORM\Column(type: "string", length: 5, options: ["default" => "RU"])]
    protected string $localization;

    #[ORM\Column(name: 'created_at', type: "datetime", updatable: false, insertable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    protected \DateTimeInterface $createdAt;
}