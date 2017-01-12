-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) 
VALUES('POCOR-3550', NOW());

-- security_functions
UPDATE `security_functions` 
SET 
`_view` = 'index|view',
`_edit` = 'edit' 
WHERE `security_functions`.`id` = 5020;
