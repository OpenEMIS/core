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