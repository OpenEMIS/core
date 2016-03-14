-- POCOR-2604
-- student_statuses
DROP TABLE `student_statuses`;

ALTER TABLE `z_2604_student_statuses` 
RENAME TO  `student_statuses` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2604';

-- 3.4.16
UPDATE config_items SET value = '3.4.16' WHERE code = 'db_version';
