DELETE FROM `student_statuses` 
WHERE `code`='PENDING_ADMISSION';

DELETE FROM `student_statuses` 
WHERE `code`='REJECTED';

ALTER TABLE `institution_student_admission` 
DROP COLUMN `type`,
CHANGE COLUMN `student_id` `security_user_id` INT(11) NOT NULL COMMENT '' , RENAME TO  `institution_student_transfers` ;

UPDATE `security_functions` SET `controller`='Dashboard', `_view`=null, `_execute`='TransferApprovals.edit' WHERE `name`='Transfer Approval';
DELETE FROM `security_functions` WHERE `id`=1028;

DELETE FROM `labels` WHERE `module`='StudentAdmission' and `field`='created';
DELETE FROM `labels` WHERE `module`='TransferApprovals' and `field`='created';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1892';