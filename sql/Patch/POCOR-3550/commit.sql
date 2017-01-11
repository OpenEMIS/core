-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) 
VALUES('POCOR-3550', NOW());

-- security_functions
UPDATE `security_functions` 
SET 
`_view` = 'index|view|AdministrativeBoundaries.index|AdministrativeBoundaries.view|Authentication.index|Authentication.view|CustomValidation.index|CustomValidation.view|ExternalDataSource.index|ExternalDataSource.view|ProductLists.index|ProductLists.view',
`_edit` = 'edit|AdministrativeBoundaries.edit|Authentication.edit|CustomValidation.edit|ExternalDataSource.edit|ProductLists.edit' 
WHERE `security_functions`.`id` = 5020;
