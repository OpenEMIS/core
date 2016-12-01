-- POCOR-3568
DROP TABLE IF EXISTS `textbooks`;

DROP TABLE IF EXISTS `textbook_conditions`;

DROP TABLE IF EXISTS `textbook_statuses`;

DROP TABLE IF EXISTS `institution_textbooks`;

DELETE FROM `labels`
WHERE `module` = 'InstitutionTextbooks';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3568';


-- POCOR-3583
-- security_functions
UPDATE `security_functions` SET `name` = 'Results' WHERE `id` IN (1015,2016,7015);


-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3583';


-- 3.7.5
UPDATE config_items SET value = '3.7.5' WHERE code = 'db_version';
