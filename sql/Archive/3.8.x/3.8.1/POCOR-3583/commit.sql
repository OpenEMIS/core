-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3583', NOW());

-- security_functions
UPDATE `security_functions` SET `name` = 'Assessments' WHERE `id` IN (1015,2016,7015);
