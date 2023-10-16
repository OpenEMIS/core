-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3660', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES ('11fd443d-f298-11e6-aa46-525400b263eb', 'ExaminationResults', 'openemis_no', 'Institutions -> Examinations -> Results', 'OpenEMIS ID', 1, 1, NOW());

-- security_functions
SET @order := 0;
SELECT `order` INTO @order FROM `security_functions` WHERE `id` = 1027;
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` BETWEEN @order AND 1999;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(1054, 'Results', 'Institutions', 'Institutions', 'Examinations', 1000, 'ExaminationResults.index|ExaminationResults.view', NULL, NULL, NULL, NULL, @order, 1, 1, NOW());
