-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3764', NOW());

-- student_statuses
UPDATE `student_statuses`
SET `code` = 'WITHDRAWN', `name` = 'Withdrawn'
WHERE `code` = 'DROPOUT';

-- student_withdraw_reasons
RENAME TABLE `student_dropout_reasons` TO `student_withdraw_reasons`;

INSERT INTO `student_withdraw_reasons` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('Dropout', 1, 1, 1, 0, 'DROPOUT', 'DROPOUT', NULL, NULL, 1, NOW());

SET @order := 1;
UPDATE `student_withdraw_reasons`
SET `order` = @order := @order + 1
WHERE `international_code` <> 'DROPOUT'
ORDER BY `order` ASC;

-- institution_student_withdraw
ALTER TABLE `institution_student_dropout`
    RENAME TO `institution_student_withdraw`,
    CHANGE COLUMN `student_dropout_reason_id` `student_withdraw_reason_id` INT(11) NOT NULL COMMENT 'links to student_withdraw_reasons.id';

-- security_functions
UPDATE `security_functions`
SET `name` = 'Withdraw Request', `_execute` = 'WithdrawRequests.add|WithdrawRequests.edit'
WHERE `name` = 'Dropout Request' AND `controller` = 'Institutions' AND `category` = 'Students';

UPDATE `security_functions`
SET `name` = 'Student Withdraw', `_view` = 'StudentWithdraw.index|StudentWithdraw.view', `_execute` = 'StudentWithdraw.edit|StudentWithdraw.view'
WHERE `name` = 'Student Dropout' AND `controller` = 'Institutions' AND `category` = 'Students';

-- labels
UPDATE `labels`
SET `module` = 'StudentWithdraw', `module_name` = 'Institutions -> Student Withdraw'
WHERE `module` = 'StudentDropout' AND `field` = 'created';

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('289ca8c0-edcd-11e6-9c46-525400b263eb', 'WithdrawRequests', 'student_withdraw_reason_id', 'Institutions -> Withdraw Requests', 'Reason', NULL, NULL, 1, NULL, NULL, 1, NOW());
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('868232d5-edd1-11e6-9c46-525400b263eb', 'StudentWithdraw', 'student_withdraw_reason_id', 'Institutions -> Student Withdraw', 'Reason', NULL, NULL, 1, NULL, NULL, 1, NOW());
