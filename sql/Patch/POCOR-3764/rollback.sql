-- student_statuses
UPDATE `student_statuses`
SET `code` = 'DROPOUT', `name` = 'Dropout'
WHERE `code` = 'WITHDRAWN';

-- student_withdraw_reasons
RENAME TABLE `student_withdraw_reasons` TO `student_dropout_reasons`;

DELETE FROM `student_dropout_reasons`
WHERE `name` = 'Dropout'
AND `international_code` = 'DROPOUT';

-- institution_student_withdraw
ALTER TABLE `institution_student_withdraw`
    RENAME TO `institution_student_dropout`,
    CHANGE COLUMN `student_withdraw_reason_id` `student_dropout_reason_id` INT(11) NOT NULL COMMENT 'links to student_dropout_reasons.id';

-- security_functions
UPDATE `security_functions`
SET `name` = 'Dropout Request', `_execute` = 'DropoutRequests.add|DropoutRequests.edit'
WHERE `name` = 'Withdraw Request' AND `controller` = 'Institutions' AND `category` = 'Students';

UPDATE `security_functions`
SET `name` = 'Student Dropout', `_view` = 'StudentDropout.index|StudentDropout.view', `_execute` = 'StudentDropout.edit|StudentDropout.view'
WHERE `name` = 'Student Withdraw' AND `controller` = 'Institutions' AND `category` = 'Students';

-- labels
UPDATE `labels`
SET `module` = 'StudentDropout', `module_name` = 'Institutions -> Student Dropout'
WHERE `module` = 'StudentWithdraw' AND `field` = 'created';

DELETE FROM `labels`
WHERE `module` = 'WithdrawRequests'
AND `field` = 'student_withdraw_reason_id';

DELETE FROM `labels`
WHERE `module` = 'StudentWithdraw'
AND `field` = 'student_withdraw_reason_id';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3764';

