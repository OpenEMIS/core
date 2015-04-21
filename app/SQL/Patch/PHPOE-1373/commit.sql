UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStudent.index|import' WHERE `module` LIKE 'Student' AND `title` LIKE 'List of Students';
UPDATE `security_functions` SET `_add` = '_view:add|import|importTemplate' WHERE `module` LIKE 'Students' AND `category` LIKE 'General' AND `name` LIKE 'Student';

ALTER TABLE `genders` ADD `code` VARCHAR(10) NOT NULL AFTER `name`;
UPDATE `genders` SET `code` = 'M' WHERE `name` LIKE 'Male';
UPDATE `genders` SET `code` = 'F' WHERE `name` LIKE 'Female';