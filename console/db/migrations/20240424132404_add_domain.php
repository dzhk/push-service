<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddDomain extends AbstractMigration
{
    public function up(): void
    {
        $sql = "
            INSERT INTO srv_push.domain (domain, rtb_widget_id, partner_id, localization, created_at)
            VALUES ('24-7all.news', 'ndjc7g36y6edhma2', 1, 'DE', NOW()),
                   ('24news-top.wiki', 'd90csezkfy8as5fk', 1, 'HU', NOW());
        ";
        $this->execute($sql);
    }

    public function down(): void
    {
    }
}
