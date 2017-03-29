-- examination_centres_examinations
DROP TABLE IF EXISTS `examination_centres_examinations`;

-- examination_centres
DROP TABLE IF EXISTS `examination_centres`;
RENAME TABLE `z_3690_examination_centres` TO `examination_centres`;

-- examination_centre_special_needs
DROP TABLE IF EXISTS `examination_centre_special_needs`;
RENAME TABLE `z_3690_examination_centre_special_needs` TO `examination_centre_special_needs`;

-- examination_centres_examinations_institutions
DROP TABLE IF EXISTS `examination_centres_examinations_institutions`;
RENAME TABLE `z_3690_examination_centres_institutions` TO `examination_centres_institutions`;

-- examination_centres_examinations_invigilators
DROP TABLE IF EXISTS `examination_centres_examinations_invigilators`;
RENAME TABLE `z_3690_examination_centres_invigilators` TO `examination_centres_invigilators`;

-- examination_centres_examinations_subjects
DROP TABLE IF EXISTS `examination_centres_examinations_subjects`;
RENAME TABLE `z_3690_examination_centre_subjects` TO `examination_centre_subjects`;

-- examination_centres_examinations_subjects_students
DROP TABLE IF EXISTS `examination_centres_examinations_subjects_students`;

-- examination_centres_examinations_students
DROP TABLE IF EXISTS `examination_centres_examinations_students`;
RENAME TABLE `z_3690_examination_centre_students` TO `examination_centre_students`;

-- examination_centre_rooms_examinations
DROP TABLE IF EXISTS `examination_centre_rooms_examinations`;

-- examination_centre_rooms
DROP TABLE IF EXISTS `examination_centre_rooms`;
RENAME TABLE `z_3690_examination_centre_rooms` TO `examination_centre_rooms`;

-- examination_centre_rooms_examinations_invigilators
DROP TABLE IF EXISTS `examination_centre_rooms_examinations_invigilators`;
RENAME TABLE `z_3690_examination_centre_rooms_invigilators` TO `examination_centre_rooms_invigilators`;

-- examination_centre_rooms_examinations_students
DROP TABLE IF EXISTS `examination_centre_rooms_examinations_students`;
RENAME TABLE `z_3690_examination_centre_room_students` TO `examination_centre_room_students`;

-- examination_item_results
DROP TABLE IF EXISTS `examination_item_results`;
RENAME TABLE `z_3690_examination_item_results` TO `examination_item_results`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3690';
