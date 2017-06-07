-- labels
DELETE FROM `labels` WHERE `id` = '11fd443d-f298-11e6-aa46-525400b263eb';

-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (1054);

SET @order := 0;
SELECT `order` INTO @order FROM `security_functions` WHERE `id` = 1027;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN @order AND 1999;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3660';
