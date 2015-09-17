-- security_functions
UPDATE `security_functions` SET `_edit` = 'StudentAttendances.edit|StudentAbsences.edit' WHERE `id` = 1014;
UPDATE `security_functions` SET `_edit` = 'StaffAttendances.edit|StaffAbsences.edit' WHERE `id` = 1018;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2063';
