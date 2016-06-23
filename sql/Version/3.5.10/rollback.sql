-- POCOR-3093
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3093';


-- POCOR-3115
-- code here
-- Staff
UPDATE `security_functions` SET `_add` = 'Staff.add|StaffUser.add' WHERE id = 1016;
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 1044;
UPDATE `security_functions` SET `order` = `order`-1 WHERE `order` >= 1018 AND `order` <= 1044;

-- Student
UPDATE `security_functions` SET `_add` = 'Students.add|StudentUser.add' WHERE id = 1012;
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 1043;
UPDATE `security_functions` SET `order` = `order`-1 WHERE `order` >= 1013 AND `order` <= 1043;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3115';


-- POCOR-3067
-- code here
-- Directoriy module
UPDATE `security_functions` SET `name` = 'Guardians' WHERE id = 7009;
UPDATE `security_functions` SET `order` = 7048 WHERE id = 7009;

UPDATE `security_functions` SET `name` = 'New Guardian Profile' WHERE id = 7047;
UPDATE `security_functions` SET `_view` = NULL WHERE id = 7047;
UPDATE `security_functions` SET `_edit` = NULL WHERE id = 7047;
UPDATE `security_functions` SET `order` = 7047 WHERE id = 7047;

UPDATE `security_functions` SET `order` = 7009 WHERE id = 7009;


-- institutions module
UPDATE `security_functions` SET `name` = 'Guardians' WHERE id = 2010;
UPDATE `security_functions` SET `category` = 'Students - General' WHERE id = 2010;

DELETE FROM `security_functions` WHERE `security_functions`.`id` = 2029;

UPDATE `security_functions` SET `order` = 2002 WHERE id = 2010;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3067';


-- 3.5.9
UPDATE config_items SET value = '3.5.9' WHERE code = 'db_version';
