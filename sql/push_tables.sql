CREATE TABLE IF NOT EXISTS elgg_push_notifications_count (id bigint(20) NOT NULL, user_guid bigint(20) NOT NULL, container_guid bigint(20) NOT NULL, count int(11) NOT NULL);
CREATE TABLE IF NOT EXISTS elgg_push_notifications_subscriptions (id bigint(20) NOT NULL, user_guid bigint(20) NOT NULL, client_id varchar(100) NOT NULL, service enum('gcm','apns') NOT NULL, token varchar(100) NOT NULL);