--
-- POCOR-2491
--
UPDATE `security_functions` SET `_edit` = 'Sessions.edit' WHERE `security_functions`.`id` = 5040;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2491';