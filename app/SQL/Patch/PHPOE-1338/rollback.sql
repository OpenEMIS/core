UPDATE `navigations` SET `pattern` = 'index$|advanced' WHERE `module` LIKE 'Institution' AND `title` LIKE 'List of Institutions';

DROP  TABLE IF EXISTS `import_mapping`;