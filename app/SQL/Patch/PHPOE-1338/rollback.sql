UPDATE `navigations` SET `pattern` = 'index$|advanced' WHERE `module` LIKE 'Institution' AND `title` LIKE 'List of Institutions';
UPDATE `security_functions` SET `_add` = 'add' WHERE `module` LIKE 'Institutions' AND `category` LIKE 'General' AND `name` LIKE 'Institution';

DROP  TABLE IF EXISTS `import_mapping`;