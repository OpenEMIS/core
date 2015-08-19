DELETE FROM `student_statuses` 
WHERE `code`='PENDING_ADMISSION';

DELETE FROM `student_statuses` 
WHERE `code`='REJECTED';

ALTER TABLE `institution_student_admission` 
DROP COLUMN `type`, 
RENAME TO  `institution_student_transfers` ;