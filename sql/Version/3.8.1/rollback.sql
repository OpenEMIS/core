-- POCOR-3568
DROP TABLE IF EXISTS `textbooks`;

DROP TABLE IF EXISTS `textbook_conditions`;

DROP TABLE IF EXISTS `textbook_statuses`;

DROP TABLE IF EXISTS `institution_textbooks`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3568';


-- 3.7.5
UPDATE config_items SET value = '3.7.5' WHERE code = 'db_version';
