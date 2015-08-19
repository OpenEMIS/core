DELETE FROM `student_statuses` 
WHERE `id`=9;

DELETE FROM `student_statuses` 
WHERE `id`=10;

ALTER TABLE `institution_student_admission` 
DROP COLUMN `type`, 
RENAME TO  `institution_student_transfers` ;