-- POCOR-3601-import
-- import_mapping
DELETE FROM `import_mapping` WHERE `model` = 'Institution.InstitutionTextbooks';

DELETE FROM `security_functions` WHERE `id` IN (1052);

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3601';


-- POCOR-3567
-- restore table
DROP TABLE IF EXISTS `import_mapping`;
RENAME TABLE `z_3567_import_mapping` TO `import_mapping`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3567';


-- POCOR-3568
DROP TABLE IF EXISTS `textbooks`;

DROP TABLE IF EXISTS `textbook_conditions`;

DROP TABLE IF EXISTS `textbook_statuses`;

DROP TABLE IF EXISTS `institution_textbooks`;

DELETE FROM `labels`
WHERE `module` = 'InstitutionTextbooks';

DELETE FROM `security_functions`
WHERE `id` = 5055, 1051, 6010;

-- re-arrange order
UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `id` BETWEEN 6000 AND 7000
AND `order` > 6003;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3568';


-- POCOR-3583
-- security_functions
UPDATE `security_functions` SET `name` = 'Results' WHERE `id` IN (1015,2016,7015);


-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3583';


-- 3.7.5
UPDATE config_items SET value = '3.7.5' WHERE code = 'db_version';
