CREATE TABLE statistic_10min (
    date_time              DateTime,
    partner_id             UInt16  DEFAULT 0,
    domain                 String  DEFAULT '',
    device_type            Int8    DEFAULT 0,
    browser                String  DEFAULT '',
    OS                     String  DEFAULT '',
    model                  String  DEFAULT '',
    country LowCardinality(String) DEFAULT '',
    tz_offset              Int16   DEFAULT 0,
    utm_source             String  DEFAULT '',
    utm_campaign           String  DEFAULT '',
    utm_term               String  DEFAULT '',
    utm_content            String  DEFAULT '',
    ab_test LowCardinality(String) DEFAULT '',
    js_loads               UInt64  DEFAULT 0,
    confirm_requests       UInt64  DEFAULT 0,
    subs                   UInt64  DEFAULT 0,
    closes                 UInt64  DEFAULT 0,
    blocked                UInt64  DEFAULT 0,
    unsubs                 UInt64  DEFAULT 0,
    notification_delivered UInt64  DEFAULT 0,
    notification_clicked   UInt64  DEFAULT 0,
    notification_closed    UInt64  DEFAULT 0,
    income_by_cpc          UInt64  DEFAULT 0,
    income_by_cpm          UInt64  DEFAULT 0,
    income_by_cpa          UInt64  DEFAULT 0
)
    ENGINE = SummingMergeTree PARTITION BY toYYYYMM(date_time)
    ORDER BY (date_time, partner_id, domain, device_type, country, utm_source, utm_campaign, utm_term, utm_content,
              ab_test, browser, OS, model, tz_offset)
    SETTINGS index_granularity = 8192;


CREATE TABLE delivery_statistic_10min (
    date_time       DateTime,
    partner_id      UInt16         DEFAULT 0,
    domain          String         DEFAULT '',
    device_type     Int8           DEFAULT 0,
    browser         String         DEFAULT '',
    OS              String         DEFAULT '',
    model           String         DEFAULT '',
    country LowCardinality(String) DEFAULT '',
    tz_offset       Int16          DEFAULT 0,
    utm_source      String         DEFAULT '',
    utm_campaign    String         DEFAULT '',
    utm_term        String         DEFAULT '',
    utm_content     String         DEFAULT '',
    ab_test LowCardinality(String) DEFAULT '',
    notification_id Int64          DEFAULT 0,
    delivered       UInt64         DEFAULT 0,
    showed          UInt64         DEFAULT 0,
    clicked         UInt64         DEFAULT 0,
    closed          UInt64         DEFAULT 0,
    income_by_cpc   UInt64         DEFAULT 0,
    income_by_cpm   UInt64         DEFAULT 0,
    income_by_cpa   UInt64         DEFAULT 0
)
    ENGINE = SummingMergeTree PARTITION BY toYYYYMM(date_time)
    ORDER BY (date_time, notification_id, domain, device_type, country, utm_source, utm_campaign, utm_term,
              utm_content, ab_test, partner_id, browser, OS, model, tz_offset)
    SETTINGS index_granularity = 8192;