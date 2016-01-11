DELETE FROM labels WHERE module = 'StudentUser' and field = 'openemis_no';
DELETE FROM labels WHERE module = 'StaffUser' and field = 'openemis_no';
DELETE FROM labels WHERE module = 'StudentAttendances' and field = 'openemis_no';
DELETE FROM labels WHERE module = 'StaffAttendances' and field = 'openemis_no';
DELETE FROM labels WHERE module = 'Directories' and field = 'openemis_no';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1808';
