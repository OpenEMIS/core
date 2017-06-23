-- POCOR-2785
-- translations
DELETE FROM `translations` WHERE `en` = '%tree_no_of_item items selected';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2785';


-- POCOR-3646
-- security_users
-- No rollback due to size of the records

ALTER TABLE `security_users`
CHANGE COLUMN `username` `username` VARCHAR(50) NULL ;

UPDATE `security_functions` SET `_add`='StudentUser.add|getUniqueOpenemisId' WHERE `id`=1043;
UPDATE `security_functions` SET `_add`='StaffUser.add|getUniqueOpenemisId' WHERE `id`=1044;

ALTER TABLE `system_errors`
CHANGE COLUMN `code` `code` INT(5) NOT NULL ;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3646';


-- POCOR-4025
-- education_grades_subjects
ALTER TABLE `education_grades_subjects` CHANGE `hours_required` `hours_required` INT(5) NOT NULL;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-4025';


-- 3.10.2
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.2' WHERE code = 'db_version';
