-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3659', NOW());

-- security_functions for Institutions > Students > Examination Results
SET @order := 0;
SELECT `order` INTO @order FROM `security_functions` WHERE `id` = 2007;
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` BETWEEN @order AND 2999;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(2030, 'Examinations', 'Students', 'Institutions', 'Students - Academic', 2000, 'ExaminationResults.index', NULL, NULL, NULL, NULL, @order, 1, 1, NOW());

-- security_functions for Directories > Students > Examination Results
SET @order := 0;
SELECT `order` INTO @order FROM `security_functions` WHERE `id` = 7016;
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` BETWEEN @order AND 7999;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(7050, 'Examinations', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentExaminationResults.index',  NULL, NULL, NULL, NULL, @order, 1, 1, NOW());
