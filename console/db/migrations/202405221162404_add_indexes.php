<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddIndexes extends AbstractMigration
{
    public function up(): void
    {
        $sql0 = "CREATE INDEX IF NOT EXISTS fcm_token_domain_index ON fcm_token (domain);";
        $sql1 = "CREATE INDEX IF NOT EXISTS fcm_token_partner_id_index ON fcm_token (partner_id DESC);";
        $this->execute($sql0);
        $this->execute($sql1);
    }

    public function down(): void
    {
    }
}
