-- Drop tables
DROP TABLE IF EXISTS `institution_subjects_rooms`;

-- room_types
DELETE FROM `room_types` WHERE `international_code` = 'CLASSROOM';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-1967';
