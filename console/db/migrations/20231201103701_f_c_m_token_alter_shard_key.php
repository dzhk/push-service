<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FCMTokenAlterShardKey extends AbstractMigration
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
        $sql = "ALTER TABLE `fcm_token` 
              CHANGE COLUMN `shard_key` `scheduled_at_offset` SMALLINT DEFAULT 0 NOT NULL,
              CHANGE COLUMN `updated_at` `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL;
              
              UPDATE `fcm_token` 
                 SET `scheduled_at_offset` = ROUND(RAND() * 59);
        ";
        $this->execute($sql);
    }

    public function down(): void
    {
        $sql = "ALTER TABLE `fcm_token` 
              CHANGE COLUMN `scheduled_at_offset` `shard_key` tinyint UNSIGNED DEFAULT 0 NOT NULL,
              CHANGE COLUMN `updated_at` `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL;

              UPDATE `fcm_token` 
                 SET `shard_key` = ROUND(RAND() * 19);  
        ";
        $this->execute($sql);
    }
}
