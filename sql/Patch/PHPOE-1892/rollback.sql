DELETE FROM `tst_demo_1`.`student_statuses` 
WHERE `code`='PENDING_ADMISSION' AND `name`='Pending Admission';

DELETE FROM `tst_demo_1`.`student_statuses` 
WHERE `code`='REJECTED' AND `name`='Rejected';

ALTER TABLE `institution_student_admission` 
DROP COLUMN `type`, 
RENAME TO  `institution_student_transfers` ;