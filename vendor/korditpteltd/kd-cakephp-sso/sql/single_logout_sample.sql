-- single_logout
CREATE TABLE `single_logout` (
  `id` CHAR(36) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `session_id` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `username` (`username`),
  INDEX `session_id` (`session_id`)
);

