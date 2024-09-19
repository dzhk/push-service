<?php
declare(strict_types=1);

namespace Api\Test\Trait;

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use DI\ContainerBuilder;
use Doctrine\ORM\EntityManager;
use Firebase\JWT\JWT;
use Slim\App;
use Src\Settings\SettingsInterface;

trait AppTestTrait
{
    use ContainerTestTrait;
    use HttpTestTrait;
    use HttpJsonTestTrait;

    protected App $app;

    /**
     * Before each test.
     */
    protected function setUp(): void
    {
        $this->setUpApp();
    }

    protected function setUpApp(): void
    {
        $containerBuilder = new ContainerBuilder();

// Set up settings
        $containerBuilder->addDefinitions(__DIR__ . '/../../config/dependencies.php');
        $container = $containerBuilder->build();
        $this->setUpContainer($container);
        $this->app = $this->container->get(App::class);
        (require __DIR__ . '/../../config/routes.php')($this->app);
        (require __DIR__ . '/../../config/middleware.php')($this->app, $this->container);
    }

    protected function getAuthToken()
    {
        $settings = $this->container->get(SettingsInterface::class)->get('jwt');
        $expire = (new \DateTime("now"))
            ->modify("+1 hour")
            ->format("Y-m-d H:i:s");

        $token = JWT::encode(['expired_at' => $expire], $settings['private_key'], $settings['algorithm']);

        return $token;
    }

    protected function getEntityManager()
    {
        return $this->container->get(EntityManager::class);
    }
}
