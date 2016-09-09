DROP TABLE `examinations`;

DROP TABLE `examination_items`;

DROP TABLE `examination_grading_types`;

DROP TABLE `examination_grading_options`;

DROP TABLE `examination_centres`;

DROP TABLE `examination_centre_subjects`;

DROP TABLE `examination_centre_special_needs`;

DROP TABLE `examination_item_results`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2378';
