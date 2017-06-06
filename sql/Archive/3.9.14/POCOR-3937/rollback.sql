-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3937';

-- import_mapping
DELETE FROM `import_mapping`
WHERE `id` IN (112, 113, 114);

-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `security_functions`.`id` = 3020;
UPDATE `security_functions` SET `_execute` = NULL WHERE `security_functions`.`id` = 7034;

DELETE FROM `security_functions`
WHERE `id` IN (3039, 7052);

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` > 3022
AND `order` < 4000;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` > 7038
AND `order` < 8000;