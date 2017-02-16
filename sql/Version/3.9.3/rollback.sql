-- POCOR-3660
-- labels
DELETE FROM `labels` WHERE `id` = '11fd443d-f298-11e6-aa46-525400b263eb';

-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (1054);

SET @order := 0;
SELECT `order` INTO @order FROM `security_functions` WHERE `id` = 1027;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN @order AND 1999;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3660';


-- POCOR-3659
-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (2030, 7050);

SET @order := 0;
SELECT `order` INTO @order FROM `security_functions` WHERE `id` = 2007;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN @order AND 2999;

SET @order := 0;
SELECT `order` INTO @order FROM `security_functions` WHERE `id` = 7016;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN @order AND 7999;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3659';


-- POCOR-3764
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


-- 3.9.2
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.2' WHERE code = 'db_version';
