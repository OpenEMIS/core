-- POCOR-3068
-- code here
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('5032', 'Backup', 'Database', 'Administration', 'Database', '5000', NULL, NULL, NULL, NULL, 'backup', '5032', '1', '1', NULL, '1', NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('5033', 'Restore', 'Database', 'Administration', 'Database', '5000', NULL, NULL, NULL, NULL, 'restore', '5033', '1', '1', NULL, '1', NOW());


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3068';


-- POCOR-3058
-- code here
UPDATE `area_administratives` SET `parent_id` = -1 WHERE `parent_id` IS NULL;
UPDATE `areas` SET `parent_id` = -1 WHERE `parent_id` IS NULL;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3058';


-- POCOR-2335
-- staff_training_needs
DROP TABLE `staff_training_needs`;
ALTER TABLE `z_2335_staff_training_needs` RENAME `staff_training_needs`;
>>>>>>> origin/POCOR-3048

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3068';


-- POCOR-3058
-- code here
UPDATE `area_administratives` SET `parent_id` = -1 WHERE `parent_id` IS NULL;
UPDATE `areas` SET `parent_id` = -1 WHERE `parent_id` IS NULL;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3058';


-- POCOR-2335
-- staff_training_needs
DROP TABLE `staff_training_needs`;
ALTER TABLE `z_2335_staff_training_needs` RENAME `staff_training_needs`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2335';


-- POCOR-2820
-- code here
ALTER TABLE `institution_student_admission`
        DROP INDEX `institution_class_id`,
        DROP COLUMN `institution_class_id`;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2820';


-- POCOR-3026
-- code here
UPDATE `security_functions` SET _view = 'Assessments.index|Results.index' WHERE id = 1015;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3026';


-- POCOR-2255
--
ALTER TABLE `institution_fee_types` CHANGE `amount` `amount` DECIMAL(11,2) NOT NULL;
ALTER TABLE `institution_fees` CHANGE `total` `total` DECIMAL(20,2) NULL DEFAULT NULL;
ALTER TABLE `student_fees` CHANGE `amount` `amount` DECIMAL(11,2) NOT NULL;

DROP TABLE IF EXISTS `fee_types`;
UPDATE `field_option_values` as `fov` set `fov`.`visible`=1 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'FeeTypes');

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2255';


-- POCOR-2376
-- institution_student_admission
ALTER TABLE `institution_student_admission`
DROP COLUMN `new_education_grade_id`,
DROP INDEX `new_education_grade_id` ;

-- labels
DELETE FROM `labels` WHERE `module` = 'TransferApprovals' AND `field` = 'new_education_grade_id';
DELETE FROM `labels` WHERE `module` = 'TransferRequests' AND `field` = 'new_education_grade_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2376';


-- POCOR-2874
-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2874';

-- 3.5.6
UPDATE config_items SET value = '3.5.6' WHERE code = 'db_version';
