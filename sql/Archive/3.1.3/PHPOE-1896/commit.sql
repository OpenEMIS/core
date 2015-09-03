INSERT INTO `db_patches` VALUES ('PHPOE-1896');

ALTER TABLE `student_behaviours` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `student_behaviours` CHANGE `institution_site_id` `institution_id` INT(11) NOT NULL;
ALTER TABLE `student_behaviours` DROP INDEX security_user_id;
ALTER TABLE `student_behaviours` DROP INDEX institution_site_id;
ALTER TABLE `student_behaviours` ADD INDEX(`student_id`);
ALTER TABLE `student_behaviours` ADD INDEX(`institution_id`);

ALTER TABLE `staff_behaviours` CHANGE `security_user_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id';
ALTER TABLE `staff_behaviours` CHANGE `institution_site_id` `institution_id` INT(11) NOT NULL;
ALTER TABLE `staff_behaviours` DROP INDEX security_user_id;
ALTER TABLE `staff_behaviours` DROP INDEX institution_site_id;
ALTER TABLE `staff_behaviours` ADD INDEX(`staff_id`);
ALTER TABLE `staff_behaviours` ADD INDEX(`institution_id`);

UPDATE `security_functions` SET `_view` = 'Programmes.index|Programmes.view' WHERE `id` = 2011;
UPDATE `security_functions` SET `_view` = 'Sections.index|Sections.view' WHERE `id` = 2012;
UPDATE `security_functions` SET `_view` = 'Classes.index|Classes.view' WHERE `id` = 2013;
UPDATE `security_functions` SET `_view` = 'Absences.index|Absences.view' WHERE `id` = 2014;
UPDATE `security_functions` SET `_view` = 'Behaviours.index|Behaviours.view' WHERE `id` = 2015;

UPDATE `security_functions` SET `_view` = 'Positions.index|Positions.view' WHERE `id` = 3012;
UPDATE `security_functions` SET `_view` = 'Sections.index|Sections.view' WHERE `id` = 3013;
UPDATE `security_functions` SET `_view` = 'Classes.index|Classes.view' WHERE `id` = 3014;
UPDATE `security_functions` SET `_view` = 'Absences.index|Absences.view' WHERE `id` = 3015;
UPDATE `security_functions` SET `_view` = 'Behaviours.index|Behaviours.view' WHERE `id` = 3017;
