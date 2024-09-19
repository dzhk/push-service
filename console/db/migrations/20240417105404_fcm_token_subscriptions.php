<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FcmTokenSubscriptions extends AbstractMigration
{
    public function up(): void
    {
        $sql = "
              ALTER TABLE fcm_token
                 ADD COLUMN subscription VARCHAR(500) DEFAULT '' AFTER token;
        ";
        $this->execute($sql);
    }

    public function down(): void
    {
    }
}
