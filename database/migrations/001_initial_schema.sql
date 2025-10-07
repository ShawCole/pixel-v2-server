-- Pixel v2 Initial Schema (UTC enforced)
CREATE DATABASE IF NOT EXISTS `pixel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pixel`;
CREATE TABLE IF NOT EXISTS `raw` (
  `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
  `received_at` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'Stored in UTC',
  `source` VARCHAR(64) NOT NULL,
  `payload_sha256` BINARY(32) NOT NULL,
  `raw_body` JSON NOT NULL,
  UNIQUE KEY `uk_payload_sha` (`payload_sha256`),
  KEY `ix_received` (`received_at`)
) ENGINE=InnoDB;

CREATE DATABASE IF NOT EXISTS `CLIENT_DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `CLIENT_DB_NAME`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `pair_ulid` CHAR(26) NOT NULL,
  `event_timestamp` DATETIME(6) NOT NULL COMMENT 'Stored in UTC',
  `payload_sha256` BINARY(32) NOT NULL,
  `received_at` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT 'Stored in UTC',
  UNIQUE KEY `uk_events_payload_sha` (`payload_sha256`),
  KEY `ix_events_pair` (`pair_ulid`)
) ENGINE=InnoDB;
