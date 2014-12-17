UPDATE `navigations` SET `visible` = 0 WHERE `module` LIKE 'Student' AND `plugin` LIKE 'Students' AND `title` LIKE 'Add new Student';

UPDATE `navigations` SET `visible` = 0 WHERE `module` LIKE 'Staff' AND `plugin` LIKE 'Staff' AND `title` LIKE 'Add new Staff';

UPDATE `navigations` SET `title` = 'Add Student' WHERE `module` LIKE 'Student' AND `plugin` LIKE 'Students' AND `title` LIKE 'Add existing Student';

UPDATE `navigations` SET `title` = 'Add Staff' WHERE `module` LIKE 'Staff' AND `plugin` LIKE 'Staff' AND `title` LIKE 'Add existing Staff';