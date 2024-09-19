<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AlterStatTables extends AbstractMigration
{
    public function up(): void
    {
        $sql = "
              alter table delivery_statistic_daily modify model varchar(40) not null;
              alter table delivery_statistic_10min modify model varchar(40) not null;
              alter table statistic_daily modify model varchar(40) not null;
              alter table statistic_10min modify model varchar(40) not null;
              alter table delivery_statistic_daily modify domain varchar(40) not null;
              alter table delivery_statistic_10min modify domain varchar(40) not null;
              alter table statistic_daily modify domain varchar(40) not null;
              alter table statistic_10min modify domain varchar(40) not null;
        ";
        $this->execute($sql);
    }

    public function down(): void
    {
    }
}
