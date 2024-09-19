CREATE TABLE notification_distribution_queue (
    message_init_date_time Datetime,
    message_id             String,
    status_queued          UInt8 DEFAULT 0,
    status_extracted       UInt8 DEFAULT 0,
    status_sent_success    UInt8 DEFAULT 0,
    status_sent_failed     UInt8 DEFAULT 0,
    status_delivered       UInt8 DEFAULT 0,
    status_showed          UInt8 DEFAULT 0,
    event_time             Datetime
) ENGINE = NATS SETTINGS
    nats_url = 'nats://192.168.20.110:4222',
    nats_subjects = 'notification_tracking.*',
    nats_format = 'JSONEachRow',
    date_time_input_format = 'best_effort';

CREATE TABLE notification_distribution_log (
    message_init_date_time Datetime,
    message_id             String,
    status_queued          UInt8 DEFAULT 0,
    status_extracted       UInt8 DEFAULT 0,
    status_sent_success    UInt8 DEFAULT 0,
    status_sent_failed     UInt8 DEFAULT 0,
    status_delivered       UInt8 DEFAULT 0,
    status_showed          UInt8 DEFAULT 0,
    event_time             Datetime
)
    ENGINE = MergeTree PARTITION BY toYYYYMM(message_init_date_time)
    ORDER BY message_init_date_time
    TTL message_init_date_time + INTERVAL 3 MONTH;


CREATE MATERIALIZED VIEW notification_distribution_consumer TO notification_distribution_log
AS
SELECT message_init_date_time,
       message_id,
       status_queued,
       status_extracted,
       status_sent_success,
       status_sent_failed,
       status_delivered,
       status_showed,
       event_time
FROM notification_distribution_queue;