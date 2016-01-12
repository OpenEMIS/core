INSERT INTO `db_patches` VALUES ('PHPOE-2063');

-- security_functions
UPDATE `security_functions` SET `_edit` = 'StudentAttendances.edit|StudentAttendances.indexEdit|StudentAbsences.edit' WHERE `id` = 1014;
UPDATE `security_functions` SET `_edit` = 'StaffAttendances.edit|StaffAttendances.indexEdit|StaffAbsences.edit' WHERE `id` = 1018;
