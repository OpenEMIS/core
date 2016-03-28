-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2675', NOW());

ALTER TABLE `institution_positions` ADD `is_homeroom` INT(1) NOT NULL DEFAULT '1' AFTER `institution_id`;

ALTER TABLE `security_roles` ADD `code` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `name`;
ALTER TABLE `security_roles` ADD INDEX(`code`);

-- updating code for preset roles
UPDATE security_roles SET code = 'PRINCIPAL' WHERE name IN ('School Principal', 'Principal');

UPDATE security_roles SET code = 'ADMINISTRATOR' WHERE name = 'Administrator';
UPDATE security_roles SET code = 'GROUP_ADMINISTRATOR' WHERE name = 'Group Administrator';
UPDATE security_roles SET code = 'TEACHER' WHERE name = 'Teacher';
UPDATE security_roles SET code = 'STAFF' WHERE name = 'Staff';
UPDATE security_roles SET code = 'STUDENT' WHERE name = 'Student';
UPDATE security_roles SET code = 'GUARDIAN' WHERE name = 'Guardian';

-- insert if not exists
SELECT (MAX(`order`)+1) into @highestOrder from security_roles;
INSERT INTO `security_roles` (`name`, `code`, `order`, `visible`, `security_group_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT 'Homeroom Teacher', 'HOMEROOM_TEACHER', @highestOrder, 1, -1, NULL, NULL, 1, '0000-00-00 00:00:00' FROM dual WHERE NOT EXISTS (SELECT 1 FROM security_roles WHERE name = 'Homeroom Teacher');

UPDATE security_roles SET code = 'HOMEROOM_TEACHER' WHERE name = 'Homeroom Teacher';