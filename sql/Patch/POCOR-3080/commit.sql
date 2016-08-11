-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3080', NOW());

-- assessment_item_grading_types
DROP TABLE IF EXISTS `assessment_item_grading_types`;
CREATE TABLE `assessment_item_grading_types` (
  `id` char(36) NOT NULL,
  `assessment_grading_type_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `assessment_item_id` int(11) NOT NULL,
  `assessment_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`assessment_grading_type_id`,`assessment_id`,`assessment_item_id`,`assessment_period_id`),
  UNIQUE (`id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- assessment_items
RENAME TABLE `assessment_items` TO `z_3080_assessment_items`;

-- backup assessment_items / assessment_grading_type_id cloumn

-- drop assessment_grading_type_id column
ALTER TABLE `assessment_items` DROP `assessment_grading_type_id`;