-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3492', NOW());

-- security_functions
UPDATE `security_functions` SET `_add`='StudentUser.add|getUniqueOpenemisId' WHERE `id`='1043';
