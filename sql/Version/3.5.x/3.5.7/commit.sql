-- POCOR-3068
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3068', NOW());


-- code here
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 5032;
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 5033;


-- POCOR-3058
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3058', NOW());


-- code here
UPDATE `area_administratives` SET `parent_id` = NULL WHERE `parent_id` = -1;
UPDATE `areas` SET `parent_id` = NULL WHERE `parent_id` = -1;


-- POCOR-2335
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2335', NOW());

-- staff_training_needs
CREATE TABLE `z_2335_staff_training_needs` LIKE `staff_training_needs`;
INSERT INTO `z_2335_staff_training_needs` SELECT * FROM `staff_training_needs`;

DELETE FROM `staff_training_needs` WHERE NOT EXISTS (
        SELECT 1 FROM `training_courses`
        WHERE `training_courses`.`id` = `staff_training_needs`.`course_id`
        )
AND `staff_training_needs`.`course_id` <> 0
AND `staff_training_needs`.`training_need_category_id` = 0;


-- POCOR-2820
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2820', NOW());


-- code here
ALTER TABLE `institution_student_admission`
        ADD COLUMN `institution_class_id` int(11) DEFAULT NULL after `education_grade_id`,
        ADD INDEX `institution_class_id` (`institution_class_id`);


-- POCOR-3026
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3026', NOW());


-- code here
UPDATE `security_functions` SET _view = 'Assessments.index|Results.index|Assessments.view' WHERE id = 1015;


-- POCOR-2255
--
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2255', NOW());

ALTER TABLE `institution_fee_types` CHANGE `amount` `amount` DECIMAL(15,2) NOT NULL;
ALTER TABLE `institution_fees` CHANGE `total` `total` DECIMAL(50,2) NULL DEFAULT NULL;
ALTER TABLE `student_fees` CHANGE `amount` `amount` DECIMAL(50,2) NOT NULL;

DROP TABLE IF EXISTS `fee_types`;
CREATE TABLE `fee_types` LIKE `institution_network_connectivities`;
INSERT INTO `fee_types`
SELECT
        `fov`.`id` as `id`,
        `fov`.`name` as `name`,
        `fov`.`order` as `order`,
        `fov`.`visible` as `visible`,
        `fov`.`editable` as `editable`,
        `fov`.`default` as `default`,
        `fov`.`international_code` as `international_code`,
        `fov`.`national_code` as `national_code`,
        `fov`.`modified_user_id` as `modified_user_id`,
        `fov`.`modified` as `modified`,
        `fov`.`created_user_id` as `created_user_id`,
        `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'FeeTypes');
UPDATE `field_option_values` as `fov` set `fov`.`visible`=0 WHERE `fov`.`field_option_id`=(SELECT `fo`.`id` FROM `field_options` as `fo` WHERE `fo`.`code` = 'FeeTypes');


-- POCOR-2376
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2376', NOW());

-- institution_student_admission
ALTER TABLE `institution_student_admission`
ADD COLUMN `new_education_grade_id` INT(11) NULL COMMENT '' AFTER `education_grade_id`,
ADD INDEX `new_education_grade_id` (`new_education_grade_id`);

UPDATE `institution_student_admission`
SET `new_education_grade_id` = `education_grade_id`
WHERE `type` = 2;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferApprovals', 'new_education_grade_id', 'Institutions -> Transfer Approvals', 'Education Grade', 1, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'TransferRequests', 'new_education_grade_id', 'Institutions -> Transfer Requests', 'Education Grade', 1, 1, NOW());


-- POCOR-2874
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2874', NOW());

 -- remove orphan
DELETE FROM `staff_custom_field_values`
WHERE NOT EXISTS (
        SELECT 1 FROM `staff_custom_fields`
                WHERE `staff_custom_fields`.`id` = `staff_custom_field_values`.`staff_custom_field_id`
        );


-- 3.5.7
UPDATE config_items SET value = '3.5.7' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
