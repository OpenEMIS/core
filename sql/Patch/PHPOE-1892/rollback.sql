DELETE FROM `student_statuses` 
WHERE `code`='PENDING_ADMISSION';

DELETE FROM `student_statuses` 
WHERE `code`='REJECTED';

ALTER TABLE `institution_student_admission` 
DROP COLUMN `type`, 
RENAME TO  `institution_student_transfers` ;

DELETE FROM `labels` WHERE `module`='StudentAdmission' and`field`='security_user_id';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1892';