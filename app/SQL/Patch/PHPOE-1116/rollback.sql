UPDATE `navigations` SET `visible` = 1 WHERE `module` LIKE 'Student' AND `plugin` LIKE 'Students' AND `title` LIKE 'Add new Student';

UPDATE `navigations` SET `visible` = 1 WHERE `module` LIKE 'Staff' AND `plugin` LIKE 'Staff' AND `title` LIKE 'Add new Staff';

UPDATE `navigations` SET `title` = 'Add existing Student' WHERE `module` LIKE 'Student' AND `plugin` LIKE 'Students' AND `title` LIKE 'Add Student';

UPDATE `navigations` SET `title` = 'Add existing Staff' WHERE `module` LIKE 'Staff' AND `plugin` LIKE 'Staff' AND `title` LIKE 'Add Staff';