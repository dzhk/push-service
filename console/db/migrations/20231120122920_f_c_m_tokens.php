<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class FCMTokens extends AbstractMigration
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
        $sql = 'CREATE TABLE `fcm_token` (
                    `token`            varchar(256)                           NOT NULL,
                    `cookie`           varchar(64)                            NOT NULL,
                    `created_at`       datetime   DEFAULT CURRENT_TIMESTAMP() NOT NULL,
                    `updated_at`       datetime   DEFAULT CURRENT_TIMESTAMP() NOT NULL,
                    `domain`           varchar(64)                            NOT NULL,
                    `news_id`          int                                    NOT NULL,
                    `news_category`    int                                    NOT NULL,
                    `device`           varchar(4)                             NOT NULL,
                    `geo`              varchar(2)                             NOT NULL,
                    `timezone_offset`  smallint   DEFAULT -180                NOT NULL COMMENT "отступ GMT от местного в минутах",
                    `shard_key`        tinyint    DEFAULT 0                   NOT NULL,
                    `timezone_changed` tinyint    DEFAULT 0                   NOT NULL,
                    `token_changed`    tinyint    DEFAULT 0                   NOT NULL,
                    `unsubbed`         tinyint(1) DEFAULT 0                   NOT NULL,
                    CONSTRAINT `token`
                        UNIQUE (`token`)
                )
                    CHARSET = utf8mb4;
                
                CREATE INDEX `cookie`
                    ON `fcm_token` (`cookie`);
        ';
        $this->execute($sql);
    }

    public function down() {
        $sql = 'DROP TABLE `fcm_token`';
        $this->execute($sql);
    }
}
