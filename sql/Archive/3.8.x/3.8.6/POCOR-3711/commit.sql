-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3711', NOW());

UPDATE `security_functions` SET `order` = `order` + 1 WHERE `id` < 2000 and `order` > 1016;
UPDATE `security_functions` SET `order` = 1017 WHERE id = 1016;
UPDATE `security_functions` SET `order` = 1018 WHERE id = 1044;
UPDATE `security_functions` SET `order` = 1019 WHERE id = 1003;

UPDATE `security_functions`
SET
`controller` = 'Institutions',
`_view` = 'Staff.index|Staff.view',
`_edit` = 'Staff.edit',
`_add` = 'Staff.add|getInstitutionPositions',
`_delete` = 'Staff.remove',
`_execute` = 'Staff.excel'
WHERE `id` = 1016;

UPDATE `security_functions`
SET
`name` = 'Overview',
`controller` = 'Institutions',
`_view` = 'StaffUser.view',
`_edit` = 'StaffUser.edit|StaffUser.pull',
`_add` = NULL,
`_delete` = NULL,
`_execute` = 'StaffUser.excel'
WHERE `id` = 3000;
