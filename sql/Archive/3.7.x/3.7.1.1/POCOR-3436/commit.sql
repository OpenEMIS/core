-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3436', NOW());

-- security_functions
UPDATE `security_functions`
SET `_execute` = 'Promotion.index|Promotion.add|Promotion.reconfirm|IndividualPromotion.index|IndividualPromotion.add|IndividualPromotion.reconfirm'
WHERE `controller` = 'Institutions' AND `name` = 'Promotion';
