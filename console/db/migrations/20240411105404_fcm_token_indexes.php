<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FcmTokenIndexes extends AbstractMigration
{
    public function up(): void
    {
        $sql = "
              ALTER TABLE `fcm_token`
                ADD INDEX `country` (`country`),
                ADD INDEX `created_at` (`created_at`),
                ADD INDEX `updated_at` (`updated_at`),
                ADD INDEX `scheduled_at_offset` (`scheduled_at_offset`)
                ;
        ";
        $this->execute($sql);
    }

    public function down(): void
    {
    }
}
