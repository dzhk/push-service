<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DefaultCreatedAt extends AbstractMigration
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
    public function up(): void
    {
        $sql = 'ALTER TABLE `notification` 
              CHANGE COLUMN `created_at` `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
                ALTER TABLE `domain` CHANGE COLUMN `created_at` `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ';
        $this->execute($sql);
    }

    public function down()
    {
        $sql = 'ALTER TABLE `notification` 
              CHANGE COLUMN `created_at` `created_at` DATETIME NOT NULL;
                ALTER TABLE `domain` 
              CHANGE COLUMN `created_at` `created_at` DATETIME NOT NULL';
        $this->execute($sql);
    }
}
