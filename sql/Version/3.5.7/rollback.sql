-- POCOR-2780
--

DROP TABLE IF EXISTS `import_mapping`;
RENAME TABLE `z_2780_import_mapping` TO `import_mapping`;

DROP TABLE IF EXISTS `security_functions`;
RENAME TABLE `z_2780_security_functions` TO `security_functions`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2780';


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


-- POCOR-2734
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2734';


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
