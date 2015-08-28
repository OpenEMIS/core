INSERT INTO `db_patches` VALUES ('PHPOE-1904');

UPDATE field_options SET plugin = 'Institution', code = 'Genders' WHERE code = 'InstitutionSiteGenders';
UPDATE field_options SET plugin = 'Institution', code = 'Localities' WHERE code = 'InstitutionSiteLocalities';
UPDATE field_options SET plugin = 'Institution', code = 'Ownerships' WHERE code = 'InstitutionSiteOwnerships';
UPDATE field_options SET plugin = 'Institution', code = 'Sectors' WHERE code = 'InstitutionSiteSectors';
UPDATE field_options SET plugin = 'Institution', code = 'Statuses' WHERE code = 'InstitutionSiteStatuses';
UPDATE field_options SET plugin = 'Institution', code = 'Types' WHERE code = 'InstitutionSiteTypes';
UPDATE field_options SET plugin = 'Institution', code = 'Types' WHERE code = 'InstitutionSiteTypes';


UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StudentAbsenceReasons';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StudentBehaviourCategories';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffAbsenceReasons';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffBehaviourCategories';

UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'LeaveStatuses';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffLeaveTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffTypes';
UPDATE field_options SET plugin = 'FieldOption' WHERE code = 'StaffStatuses';


DELETE from field_options where plugin = 'Students' and code = 'StudentCategories' and name = 'Categories' and parent = 'Student';
DELETE from field_options where plugin = 'Students' and code = 'Genders' and name = 'Gender' and parent = 'Student';
DELETE from field_options where plugin = 'Students' and code = 'StudentStatuses' and name = 'Status' and parent = 'Student';