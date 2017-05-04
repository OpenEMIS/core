-- webhooks
CREATE TABLE `webhooks` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  `status` INT(1) NOT NULL COMMENT '0 -> Inactive, 1 -> Active',
  `url` VARCHAR(200) NOT NULL,
  `method` VARCHAR(10) NOT NULL COMMENT 'POST -> HTTP Post Method, GET -> HTTP Get Method, PUT -> HTTP Put Method, DELETE -> HTTP Delete Method, PATCH -> HTTP Patch Method',
  `description` TEXT NULL,
  `modified_user_id` INT(11) NULL,
  `modified` DATETIME NULL,
  `created_user_id` INT(11) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of webhooks';

-- webhook_events
CREATE TABLE `webhook_events` (
  `webhook_id` INT(11) NOT NULL,
  `event_key` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`webhook_id`, `event_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of webhook events';
