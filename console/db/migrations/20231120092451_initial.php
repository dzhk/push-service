<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Psr\Log\LoggerInterface;

final class Initial extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(/*\App\Application\Settings\SettingsInterface $settings*/): void
    {
//        $dbSettings = $settings->get('db');
//        $this->execute('CREATE DATABASE :dbName COLLATE `utf8mb4_bin` CHARACTER SET \'utf8mb4\'', [
//            'dbName' => $dbSettings['name']
//        ]);
//        $this->execute('GRANT ALL PRIVILEGES ON :dbNmae.* TO \':userName\'@\'%\'', [
//            'dbName' => $dbSettings['name'],
//            'userName' => $dbSettings['user'],
//        ]);
    }
}
