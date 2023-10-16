-- Restore tables
DROP TABLE IF EXISTS `examinations`;
RENAME TABLE `z_3501_examinations` TO `examinations`;

DROP TABLE IF EXISTS `examination_centres`;
RENAME TABLE `z_3501_examination_centres` TO `examination_centres`;

DROP TABLE IF EXISTS `examination_centre_special_needs`;
RENAME TABLE `z_3501_examination_centre_special_needs` TO `examination_centre_special_needs`;

DROP TABLE IF EXISTS `examination_centre_students`;
RENAME TABLE `z_3501_examination_centre_students` TO `examination_centre_students`;

DROP TABLE IF EXISTS `examination_centre_subjects`;
RENAME TABLE `z_3501_examination_centre_subjects` TO `examination_centre_subjects`;

DROP TABLE IF EXISTS `examination_centres_invigilators`;
DROP TABLE IF EXISTS `examination_centre_rooms`;
DROP TABLE IF EXISTS `examination_centre_room_students`;
DROP TABLE IF EXISTS `examination_centre_rooms_invigilators`;
DROP TABLE IF EXISTS `examination_centres_institutions`;

DROP TABLE IF EXISTS `examination_items`;
RENAME TABLE `z_3501_examination_items` TO `examination_items`;

DROP TABLE IF EXISTS `examination_item_results`;

DELETE FROM `import_mapping` WHERE `model` = 'Examination.ExaminationItemResults';

UPDATE `security_functions` SET `order`='5046' WHERE `id`='5046';
UPDATE `security_functions` SET `_add`= NULL, `order`='5047' WHERE `id`='5047';
UPDATE `security_functions` SET `order`='5048' WHERE `id`='5048';
UPDATE `security_functions` SET `order`='6003' WHERE `id`='6003';
UPDATE `security_functions` SET `order`='6004' WHERE `id`='6004';

DELETE FROM `security_functions` WHERE `id` IN (5051, 5052, 5053, 5054, 6009);

DELETE FROM `labels` WHERE `id` = 'dce3109a-ad53-11e6-bad3-525400b263eb';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3501';
