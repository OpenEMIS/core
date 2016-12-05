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