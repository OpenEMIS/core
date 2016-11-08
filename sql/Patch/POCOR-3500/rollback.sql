-- Restore tables
DROP TABLE IF EXISTS `examinations`;
RENAME TABLE `z_3500_examinations` TO `examinations`;

DROP TABLE IF EXISTS `examination_centres`;
RENAME TABLE `z_3500_examination_centres` TO `examination_centres`;

DROP TABLE IF EXISTS `examination_centre_special_needs`;
RENAME TABLE `z_3500_examination_centre_special_needs` TO `examination_centre_special_needs`;

DROP TABLE IF EXISTS `examination_centre_students`;
RENAME TABLE `z_3500_examination_centre_students` TO `examination_centre_students`;

DROP TABLE IF EXISTS `examination_centre_subjects`;
RENAME TABLE `z_3500_examination_centre_subjects` TO `examination_centre_subjects`;

DROP TABLE IF EXISTS `examination_centre_invigilators`;
DROP TABLE IF EXISTS `examination_centre_rooms`;
DROP TABLE IF EXISTS `examination_centre_room_students`;
DROP TABLE IF EXISTS `examination_centre_room_invigilators`;
DROP TABLE IF EXISTS `examination_item_results`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3500';
