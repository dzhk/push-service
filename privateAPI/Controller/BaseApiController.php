<?php
declare(strict_types=1);

namespace Api\Controller;

use Psr\Log\LoggerInterface;
use Src\Settings\SettingsInterface;
use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BaseApiController
{

    protected ContainerInterface $container;

    public function __construct(
        ContainerInterface $container
    )
    {
        $this->container = $container;
    }

    protected function getSettings($settingsName)
    {
        return $this->container->get(SettingsInterface::class)->get($settingsName);
    }

    protected function entityManager(): EntityManager
    {
        return $this->container->get(EntityManager::class);
    }

    protected function pdo()
    {
        return $this->container->get(\PDO::class);
    }

    protected function validation()
    {
        return $this->container->get(ValidatorInterface::class);
    }

    protected function validate($constraints, $data, ResponseInterface $response): void
    {
        $violations = $this->validation()->validate($data, $constraints);
        if ($violations->count()) {
            throw new ValidationFailedException($data, $violations);
        }
    }

    protected function logger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }
}