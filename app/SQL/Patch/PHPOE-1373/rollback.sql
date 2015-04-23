UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStudent.index' WHERE `module` LIKE 'Student' AND `title` LIKE 'List of Students';
UPDATE `security_functions` SET `_add` = '_view:add', `_view` = 'view|InstitutionSiteStudent|InstitutionSiteStudent.index' 
WHERE `module` LIKE 'Students' AND `category` LIKE 'General' AND `name` LIKE 'Student';

UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStaff.index' WHERE `module` LIKE 'Staff' AND `title` LIKE 'List of Staff';
UPDATE `security_functions` SET `_add` = '_view:add', `_view` = 'view|InstitutionSiteStaff|InstitutionSiteStaff.index' 
WHERE `module` LIKE 'Staff' AND `category` LIKE 'General' AND `name` LIKE 'Staff';

UPDATE `security_functions` SET `_add` = '_view:import|importTemplate|add' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'General' AND `name` LIKE 'Institution';

ALTER TABLE `genders` DROP `code`;

DELETE FROM `import_mapping` WHERE `model` LIKE 'Student' OR `model` LIKE 'Staff';