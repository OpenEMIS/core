-- security_functions
UPDATE `security_functions`
SET `_execute` = 'Promotion.index|Promotion.add|Promotion.reconfirm'
WHERE `controller` = 'Institutions' AND `name` = 'Promotion';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3436';
