<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitShema2 extends AbstractMigration
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
        $sql = '
            CREATE TABLE partner (
                id         int UNSIGNED AUTO_INCREMENT,
                name       varchar(64) NOT NULL,
                created_at datetime    NOT NULL,
                CONSTRAINT partner_pk
                    PRIMARY KEY (id)
            )
                COLLATE = utf8mb4_bin;
            
            CREATE TABLE domain (
                domain        varchar(512)            NOT NULL,
                rtb_widget_id varchar(16)             NOT NULL,
                partner_id    int UNSIGNED            NOT NULL,
                localization  varchar(5) DEFAULT "RU" NOT NULL,
                created_at    datetime                NOT NULL,
                CONSTRAINT domain_pk2
                    PRIMARY KEY (domain),
                CONSTRAINT domain_pk
                    UNIQUE (domain)
            )
                COLLATE = utf8mb4_bin;
            
            CREATE TABLE notification (
                id           int UNSIGNED AUTO_INCREMENT,
                title        varchar(200)            NOT NULL,
                description  varchar(250) DEFAULT "" NULL,
                img          varchar(1000)           NOT NULL,
                link         varchar(1000)           NOT NULL,
                localization varchar(5)              NOT NULL,
                scheduled_at datetime                NOT NULL,
                created_at   datetime                NOT NULL,
                CONSTRAINT notification_pk
                    PRIMARY KEY (id)
            )
                COLLATE = utf8mb4_bin;
            
            RENAME TABLE fcm_token to fcm_token_legacy;
            
            CREATE TABLE fcm_token (
                token            varchar(255)                                 NOT NULL,
                user_id          varchar(50)                                  NOT NULL,
                partner_id       int UNSIGNED                                 NOT NULL,
                domain           varchar(512)                                 NOT NULL,
                page             varchar(1000)                                NOT NULL,
                device_type      tinyint UNSIGNED                             NOT NULL,
                browser          varchar(20)                                  NOT NULL,
                OS               varchar(20)                                  NOT NULL,
                country          varchar(2)                                   NOT NULL,
                tz_offset        smallint                                     NOT NULL,
                utm_source       varchar(255)                                 NOT NULL,
                utm_campaign     varchar(255)                                 NOT NULL,
                utm_term         varchar(255)                                 NOT NULL,
                utm_content      varchar(255)                                 NOT NULL,
                utm_medium       varchar(255)                                 NOT NULL,
                clickid          varchar(255)                                 NOT NULL,
                ab_test          varchar(255)                                 NOT NULL,
                user_agent       varchar(512)                                 NOT NULL,
                ip_v4            int UNSIGNED                                 NOT NULL,
                ip_v6            varbinary(16)                                NOT NULL,
                shard_key        tinyint UNSIGNED DEFAULT 0                   NOT NULL,
                timezone_changed tinyint UNSIGNED DEFAULT 0                   NOT NULL,
                token_changed    tinyint UNSIGNED DEFAULT 0                   NOT NULL,
                unsub            tinyint UNSIGNED DEFAULT 0                   NOT NULL,
                created_at       datetime         DEFAULT CURRENT_TIMESTAMP() NOT NULL,
                updated_at       datetime         DEFAULT CURRENT_TIMESTAMP() NOT NULL,
                CONSTRAINT fcm_token_pk
                    PRIMARY KEY (token)
            )
                COLLATE = utf8mb4_bin;
            
            CREATE TABLE statistic_10min (
                date_time              datetime                NOT NULL,
                unique_key             varchar(40)             NOT NULL,
                partner_id             int UNSIGNED            NOT NULL,
                domain                 varchar(20)             NOT NULL,
                device_type            tinyint UNSIGNED        NOT NULL,
                browser                varchar(20)             NOT NULL,
                OS                     varchar(20)             NOT NULL,
                model                  varchar(20)             NOT NULL,
                country                varchar(2)              NOT NULL,
                tz_offset              smallint                NOT NULL,
                utm_source             varchar(255)            NOT NULL,
                utm_campaign           varchar(255)            NOT NULL,
                utm_term               varchar(255)            NOT NULL,
                utm_content            varchar(255)            NOT NULL,
                ab_test                varchar(255) DEFAULT "" NOT NULL,
                js_loads               int UNSIGNED DEFAULT 0  NOT NULL,
                confirm_requests       int UNSIGNED DEFAULT 0  NOT NULL,
                subs                   int UNSIGNED DEFAULT 0  NOT NULL,
                closes                 int UNSIGNED DEFAULT 0  NOT NULL,
                blocked                int UNSIGNED DEFAULT 0  NOT NULL,
                unsubs                 int UNSIGNED DEFAULT 0  NOT NULL,
                notification_delivered int UNSIGNED DEFAULT 0  NOT NULL,
                notification_clicked   int UNSIGNED DEFAULT 0  NOT NULL,
                notification_closed    int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpc          int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpm          int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpa          int UNSIGNED DEFAULT 0  NOT NULL,
                CONSTRAINT statistic_10min_pk
                    UNIQUE (date_time, unique_key)
            )
                COLLATE = utf8mb4_bin;
            
            CREATE INDEX statistic_10min_datetime_index
                ON statistic_10min (date_time);
            
            CREATE INDEX statistic_10min_utm_source_index
                ON statistic_10min (utm_source);
            
            
            CREATE TABLE statistic_daily (
                date_time              datetime                NOT NULL,
                unique_key             varchar(40)             NOT NULL,
                partner_id             int UNSIGNED            NOT NULL,
                domain                 varchar(20)             NOT NULL,
                device_type            tinyint UNSIGNED        NOT NULL,
                browser                varchar(20)             NOT NULL,
                OS                     varchar(20)             NOT NULL,
                model                  varchar(20)             NOT NULL,
                country                varchar(2)              NOT NULL,
                tz_offset              smallint                NOT NULL,
                utm_source             varchar(255)            NOT NULL,
                utm_campaign           varchar(255)            NOT NULL,
                utm_term               varchar(255)            NOT NULL,
                utm_content            varchar(255)            NOT NULL,
                ab_test                varchar(255) DEFAULT "" NOT NULL,
                js_loads               int UNSIGNED DEFAULT 0  NOT NULL,
                confirm_requests       int UNSIGNED DEFAULT 0  NOT NULL,
                subs                   int UNSIGNED DEFAULT 0  NOT NULL,
                closes                 int UNSIGNED DEFAULT 0  NOT NULL,
                blocked                int UNSIGNED DEFAULT 0  NOT NULL,
                unsubs                 int UNSIGNED DEFAULT 0  NOT NULL,
                notification_delivered int UNSIGNED DEFAULT 0  NOT NULL,
                notification_clicked   int UNSIGNED DEFAULT 0  NOT NULL,
                notification_closed    int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpc          int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpm          int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpa          int UNSIGNED DEFAULT 0  NOT NULL,
                CONSTRAINT statistic_daily_pk
                    UNIQUE (date_time, unique_key)
            )
                COLLATE = utf8mb4_bin;
            
            CREATE INDEX statistic_daily_datetime_index
                ON statistic_daily (date_time);
            
            CREATE INDEX statistic_daily_utm_source_index
                ON statistic_daily (utm_source);
            
            
            
            CREATE TABLE delivery_statistic_10min (
                date_time       datetime                NOT NULL,
                unique_key      varchar(40)             NOT NULL,
                partner_id      int UNSIGNED            NOT NULL,
                domain          varchar(20)             NOT NULL,
                device_type     tinyint UNSIGNED        NOT NULL,
                browser         varchar(20)             NOT NULL,
                OS              varchar(20)             NOT NULL,
                model           varchar(20)             NOT NULL,
                country         varchar(2)              NOT NULL,
                tz_offset       smallint                NOT NULL,
                utm_source      varchar(255)            NOT NULL,
                utm_campaign    varchar(255)            NOT NULL,
                utm_term        varchar(255)            NOT NULL,
                utm_content     varchar(255)            NOT NULL,
                ab_test         varchar(255) DEFAULT "" NOT NULL,
                notification_id int UNSIGNED            NOT NULL,
            
                delivered       int UNSIGNED DEFAULT 0  NOT NULL,
                clicked         int UNSIGNED DEFAULT 0  NOT NULL,
                closed          int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpc   int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpm   int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpa   int UNSIGNED DEFAULT 0  NOT NULL,
                CONSTRAINT delivery_statistic_10min_pk
                    UNIQUE (date_time, unique_key)
            )
                COLLATE = utf8mb4_bin;
            
            CREATE INDEX delivery_statistic_10min_datetime_index
                ON delivery_statistic_10min (date_time);
            
            CREATE TABLE delivery_statistic_daily (
                date_time       datetime                NOT NULL,
                unique_key      varchar(40)             NOT NULL,
                partner_id      int UNSIGNED            NOT NULL,
                domain          varchar(20)             NOT NULL,
                device_type     tinyint UNSIGNED        NOT NULL,
                browser         varchar(20)             NOT NULL,
                OS              varchar(20)             NOT NULL,
                model           varchar(20)             NOT NULL,
                country         varchar(2)              NOT NULL,
                tz_offset       smallint                NOT NULL,
                utm_source      varchar(255)            NOT NULL,
                utm_campaign    varchar(255)            NOT NULL,
                utm_term        varchar(255)            NOT NULL,
                utm_content     varchar(255)            NOT NULL,
                ab_test         varchar(255) DEFAULT "" NOT NULL,
                notification_id int UNSIGNED            NOT NULL,
            
                delivered       int UNSIGNED DEFAULT 0  NOT NULL,
                clicked         int UNSIGNED DEFAULT 0  NOT NULL,
                closed          int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpc   int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpm   int UNSIGNED DEFAULT 0  NOT NULL,
                income_by_cpa   int UNSIGNED DEFAULT 0  NOT NULL,
                CONSTRAINT delivery_statistic_daily_pk
                    UNIQUE (date_time, unique_key)
            )
                COLLATE = utf8mb4_bin;
                
            CREATE INDEX delivery_statistic_daily_datetime_index
                ON delivery_statistic_daily (date_time);
            
            CREATE INDEX delivery_statistic_daily_utm_source_index
                ON delivery_statistic_daily (utm_source);
        ';
        $this->execute($sql);


    }

    public function down() {
        $sql = '
            DROP TABLE delivery_statistic_daily;
            DROP TABLE delivery_statistic_10min;
            DROP TABLE statistic_daily;
            DROP TABLE statistic_10min;
            DROP TABLE fcm_token;
            RENAME TABLE fcm_token_legacy to fcm_token;
            DROP TABLE notification;
            DROP TABLE partner;
            DROP TABLE domain;
        ';
        $this->execute($sql);
    }
}
