-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-2828', NOW());

UPDATE `security_functions` SET `_add`='StaffUser.add|getUniqueOpenemisId' WHERE `id`='1044';
UPDATE `security_functions` SET `_add`='Staff.add|getInstitutionPositions' WHERE `id`='1016';
UPDATE `security_functions` SET `_view`='Directories.index|Directories.view', `_edit`='Directories.edit|Directories.pull', `_add`='Directories.add', `_delete`='Directories.remove' WHERE `id`='7000';
