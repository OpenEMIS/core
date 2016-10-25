-- POCOR-3436
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3436', NOW());

-- security_functions
UPDATE `security_functions`
SET `_execute` = 'Promotion.index|Promotion.add|Promotion.reconfirm|IndividualPromotion.index|IndividualPromotion.add|IndividualPromotion.reconfirm'
WHERE `controller` = 'Institutions' AND `name` = 'Promotion';


-- 3.7.1.1
UPDATE config_items SET value = '3.7.1.1' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
