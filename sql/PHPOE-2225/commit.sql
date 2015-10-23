-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-2225', NOW());

-- workflow_status
CREATE TABLE `workflow_statuses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `workflow_model_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id`));

INSERT INTO `workflow_status` (`workflow_model_id`, `name`)
VALUES (2, 'Completed');
INSERT INTO `workflow_status` (`workflow_model_id`, `name`)
VALUES (2, 'Not Completed');

-- workflow_status_mapping
CREATE TABLE `workflow_status_mappings` (
  `id` CHAR(36) NOT NULL COMMENT '',
  `workflow_status_id` INT NOT NULL,
  `workflow_step_id` INT NOT NULL,
  PRIMARY KEY (`id`));

INSERT INTO `workflow_status_mapping` (`id`, `workflow_status_id`, `workflow_step_id`)
VALUES (uuid(), 1, 6);
INSERT INTO `workflow_status_mapping` (`id`, `workflow_status_id`, `workflow_step_id`)
VALUES (uuid(), 2, 4);
INSERT INTO `workflow_status_mapping` (`id`, `workflow_status_id`, `workflow_step_id`)
VALUES (uuid(), 2, 5);