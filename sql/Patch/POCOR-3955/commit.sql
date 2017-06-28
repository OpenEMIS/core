-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3955', NOW());


-- security_functions
CREATE TABLE `z_3955_security_functions` LIKE `security_functions`;
INSERT `z_3955_security_functions` SELECT * FROM `security_functions`;

UPDATE `security_functions`
SET `name` = 'Trainings', `_view` = 'Trainings.index', `_add` = 'Trainings.add', `_execute` = 'Trainings.download'
WHERE `security_functions`.`id` = 6011;
