INSERT INTO `db_patches` VALUES ('PHPOE-1808');

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES 
(uuid(), 'StudentUser', 'openemis_no', 'Institutions -> Students -> General', 'OpenEMIS ID', 1, NOW()),
(uuid(), 'StaffUser', 'openemis_no', 'Institutions -> Staff -> General', 'OpenEMIS ID', 1, NOW()),
(uuid(), 'StudentAttendances', 'openemis_no', 'Institutions -> Students -> General', 'OpenEMIS ID', 1, NOW()),
(uuid(), 'StaffAttendances', 'openemis_no', 'Institutions -> Staff -> General', 'OpenEMIS ID', 1, NOW());