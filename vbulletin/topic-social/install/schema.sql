CREATE TABLE IF NOT EXISTS `plugin_topicsocial_status` (
  `threadid` INT UNSIGNED NOT NULL,
  `last_attempt_at` DATETIME NULL,
  `last_success_at` DATETIME NULL,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `sent_to_telegram` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `sent_to_x` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `last_message_text` MEDIUMTEXT NULL,
  `last_url` VARCHAR(1000) NULL,
  `last_image_url` VARCHAR(1000) NULL,
  `last_error` MEDIUMTEXT NULL,
  PRIMARY KEY (`threadid`),
  KEY `status` (`status`),
  KEY `last_success_at` (`last_success_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `plugin_topicsocial_log` (
  `logid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `threadid` INT UNSIGNED NOT NULL,
  `channel` VARCHAR(32) NOT NULL,
  `attempted_at` DATETIME NOT NULL,
  `success` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  `message_text` MEDIUMTEXT NULL,
  `target_url` VARCHAR(1000) NULL,
  `image_url` VARCHAR(1000) NULL,
  `response_text` MEDIUMTEXT NULL,
  `error_text` MEDIUMTEXT NULL,
  PRIMARY KEY (`logid`),
  KEY `threadid` (`threadid`),
  KEY `channel` (`channel`),
  KEY `attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
