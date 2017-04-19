-- POCOR-3927
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3927', NOW());


-- staff_trainings
RENAME TABLE `staff_trainings` TO `z_3927_staff_trainings`;

CREATE TABLE `staff_trainings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(60) NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `credit_hours` INT(5) NOT NULL DEFAULT '0',
    `file_name` VARCHAR(250) NULL,
    `file_content` LONGBLOB NULL,
    `completed_date` DATE DEFAULT NULL,
    `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
    `staff_training_category_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to staff_training_categories.id',
    `training_field_of_study_id` INT(11) NULL DEFAULT '0' COMMENT 'links to training_field_of_studies.id',
    `modified_user_id` INT(11) DEFAULT NULL,
    `modified` DATETIME DEFAULT NULL,
    `created_user_id` INT(11) NOT NULL,
    `created` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `staff_id` (`staff_id`),
    KEY `staff_training_category_id` (`staff_training_category_id`),
    KEY `training_field_of_study_id` (`training_field_of_study_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all training of staff';

-- insert value to the staff_training table from backup staff_training
INSERT INTO `staff_trainings` (`id`, `code`, `name`, `description`, `credit_hours`, `file_name`, `file_content`, `completed_date`, `staff_id`, `staff_training_category_id`, `training_field_of_study_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Z`.`id`, NULL, `cat`.`name`, NULL, 0, NULL, NULL, `Z`.`completed_date`, `Z`.`staff_id`, `Z`.`staff_training_category_id`, NULL, `Z`.`modified_user_id`, `Z`.`modified`, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3927_staff_trainings` AS `Z`
LEFT JOIN `staff_training_categories` AS `cat` on `cat`.`id` = `Z`.`staff_training_category_id`;

-- alerts Table
INSERT INTO `alerts` (`name`, `process_name`, `process_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('LicenseRenewal', 'AlertLicenseRenewal', NULL, NULL, NULL, '1', NOW());

-- alerts rule Table
ALTER TABLE `alert_rules` CHANGE `threshold` `threshold` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- security_functions
CREATE TABLE `z_3927_security_functions`  LIKE `security_functions`;
INSERT INTO `z_3927_security_functions` SELECT * FROM `security_functions`;

-- staff controller
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 3011;

UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN 3012 AND 3038;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('3011', 'Courses', 'Staff', 'Institutions', 'Staff - Training', 3000, 'StaffTrainings.index|StaffTrainings.view', 'StaffTrainings.edit', 'StaffTrainings.add', 'StaffTrainings.remove', 'StaffTrainings.download', 3038, '1', NULL, NULL, NULL, 1, NOW());


-- directories controller
UPDATE `security_functions`
SET `name` = 'Courses',
    `_execute` = 'StaffTrainings.download',
    `category` = 'Staff - Training',
    `order` = '7100'
WHERE `id` = 7032;

UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN 7035 AND 7050;
UPDATE `security_functions` SET `order` = '7050' WHERE `id` = 7032;


-- POCOR-3876
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3876', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `mo                                                                                                           dified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('b7b9aad6-1ff1-11e7-a840-525400b263eb', 'InstitutionClasses', 'multigrade', 'Institutions -> Class                                                                                                           es', 'Multi-grade', NULL, NULL, '1', NULL, NULL, '1', '2017-04-13');


-- 3.9.11
UPDATE config_items SET value = '3.9.11' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
