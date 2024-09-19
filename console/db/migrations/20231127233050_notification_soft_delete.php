<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NotificationSoftDelete extends AbstractMigration
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
        $sql = "ALTER TABLE `notification` 
              ADD COLUMN `status` enum('active','deleted','sent') NOT NULL DEFAULT 'active';

              CREATE INDEX notification_status
                ON `notification` (`status`);      

              CREATE INDEX notification_scheduled_at
                ON `notification` (`scheduled_at`);   
        ";
        $this->execute($sql);
    }

    public function down(): void
    {
        $sql = "
            DROP INDEX `notification_scheduled_at` ON `notification`;
            DROP INDEX `notification_status` ON `notification`;
            ALTER TABLE `notification` 
              DROP COLUMN `status`";
        $this->execute($sql);
    }
}
