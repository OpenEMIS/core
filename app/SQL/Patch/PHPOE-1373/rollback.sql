UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStudent.index' WHERE `module` LIKE 'Student' AND `title` LIKE 'List of Students';
UPDATE `security_functions` SET `_add` = '_view:add' WHERE `module` LIKE 'Students' AND `category` LIKE 'General' AND `name` LIKE 'Student';

ALTER TABLE `genders` DROP `code`;