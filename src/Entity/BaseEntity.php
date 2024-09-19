<?php
declare(strict_types=1);

namespace Src\Entity;

abstract class BaseEntity
{
    public function __set($property, $value)
    {
        $this->$property = $value;
    }

    public function __get($property)
    {
        return $this->$property;
    }
}