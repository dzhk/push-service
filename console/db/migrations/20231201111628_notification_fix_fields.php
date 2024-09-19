<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NotificationFixFields extends AbstractMigration
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
        $sql = "
              DROP INDEX `notification_status` ON `notification`; 
              
              ALTER TABLE `notification` 
              CHANGE COLUMN `status` `is_deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0,
              ADD COLUMN `content_id`  INT UNSIGNED NOT NULL AFTER `id`;

              CREATE INDEX notification_is_deleted
                ON `notification` (`is_deleted`); 
        ";
        $this->execute($sql);
    }

    public function down(): void
    {
        $sql = "
            DROP INDEX `notification_is_deleted` ON `notification`;

            ALTER TABLE `notification` 
              CHANGE COLUMN `is_deleted`  `status` enum('active','deleted','sent') NOT NULL DEFAULT 'active',
              DROP COLUMN content_id; 
             
            CREATE INDEX notification_status
                ON `notification` (`status`);    
            ";
        $this->execute($sql);
    }
}
