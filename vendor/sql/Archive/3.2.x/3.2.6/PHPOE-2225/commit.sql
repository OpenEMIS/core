-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2225', NOW());

-- workflow_status
DROP TABLE IF EXISTS `workflow_statuses`;
CREATE TABLE IF NOT EXISTS `workflow_statuses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `workflow_model_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `workflow_statuses` (`workflow_model_id`, `name`)
VALUES (2, 'Completed');
INSERT INTO `workflow_statuses` (`workflow_model_id`, `name`)
VALUES (2, 'Not Completed');

-- workflow_status_mapping
DROP TABLE IF EXISTS `workflow_status_mappings`;
CREATE TABLE IF NOT EXISTS `workflow_status_mappings` (
  `id` CHAR(36) NOT NULL,
  `workflow_status_id` INT NOT NULL,
  `workflow_step_id` INT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `workflow_status_mappings` (`id`, `workflow_status_id`, `workflow_step_id`)
VALUES (uuid(), 1, (SELECT `id` FROM `workflow_steps` WHERE `stage` = 2 AND `workflow_id` = (SELECT `id` FROM `workflows` WHERE `workflow_model_id` = 2)));
INSERT INTO `workflow_status_mappings` (`id`, `workflow_status_id`, `workflow_step_id`)
VALUES (uuid(), 2, (SELECT `id` FROM `workflow_steps` WHERE `stage` = 0 AND `workflow_id` = (SELECT `id` FROM `workflows` WHERE `workflow_model_id` = 2)));
INSERT INTO `workflow_status_mappings` (`id`, `workflow_status_id`, `workflow_step_id`)
VALUES (uuid(), 2, (SELECT `id` FROM `workflow_steps` WHERE `stage` = 1 AND `workflow_id` = (SELECT `id` FROM `workflows` WHERE `workflow_model_id` = 2)));
