<?php
declare(strict_types=1);

namespace Src\Entity;

use Doctrine\ORM\Mapping as ORM;
use ReflectionClass;
use ReflectionProperty;

#[ORM\Entity]
#[ORM\Table(name: 'notification')]
class Notification extends BaseEntity implements \JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\Column(name: 'content_id', type: 'integer')]
    protected $contentId;
    #[ORM\Column(type: 'string', length: 200)]
    protected $title;

    #[ORM\Column(type: 'string', length: 250, nullable: true, options: ['default' => ''])]
    protected $description;

    #[ORM\Column(type: 'string', length: 1000)]
    protected $img;

    #[ORM\Column(type: 'string', length: 1000)]
    protected $link;

    #[ORM\Column(type: 'string', length: 5)]
    protected $localization;

    #[ORM\Column(name: 'scheduled_at', type: 'datetime')]
    protected $scheduledAt;

    #[ORM\Column(name: 'created_at', type: 'datetime', updatable: false, insertable: false)]
    protected $createdAt;

    #[ORM\Column(name: 'is_deleted', type: 'integer', options: ['default' => 0])]
    protected $isDeleted;


    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeInterface $scheduledAt): void
    {
        $this->scheduledAt = $scheduledAt;
    }

    public function jsonSerialize(): mixed
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            $result[$this->toCamelCase($property->getName())] = $property->getValue($this);
        }
        return $result;
    }

    private function toCamelCase($name) {
        $name = lcfirst($name);
        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        return $name;
    }
}