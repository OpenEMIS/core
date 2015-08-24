DELETE FROM `student_statuses` 
WHERE `code`='PENDING_ADMISSION';

DELETE FROM `student_statuses` 
WHERE `code`='REJECTED';

ALTER TABLE `institution_student_admission` 
DROP COLUMN `type`,
CHANGE COLUMN `student_id` `security_user_id` INT(11) NOT NULL COMMENT '' , RENAME TO  `institution_student_transfers` ;

DELETE FROM `labels` WHERE `module`='StudentAdmission' and `field`='student_id';
DELETE FROM `labels` WHERE `module`='StudentAdmission' and `field`='created';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1892';