-- db_patches
INSERT INTO db_patches VALUES ('PHPOE-2310', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute`='StudentAttendances.excel|StudentAbsences.excel' WHERE `id`=1014;
UPDATE `security_functions` SET `_execute`='StaffAttendances.excel|StaffAbsences.excel' WHERE `id`=1018;
