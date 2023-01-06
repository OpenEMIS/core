-- POCOR-3492
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3492', NOW());

-- security_functions
UPDATE `security_functions` SET `_add`='StudentUser.add|getUniqueOpenemisId' WHERE `id`='1043';


-- 3.7.1.2
UPDATE config_items SET value = '3.7.1.2' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
