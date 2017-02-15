
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3633', NOW());

-- security_function
UPDATE `security_functions` SET `_edit`='StudentUser.edit|StudentUser.pull' WHERE `id`='2000';
