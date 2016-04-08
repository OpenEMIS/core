--
-- POCOR-2491
--
INSERT INTO `db_patches` VALUES ('POCOR-2491', NOW());

UPDATE `security_functions` SET `_edit` = 'Sessions.edit|Sessions.template' WHERE `security_functions`.`id` = 5040;
