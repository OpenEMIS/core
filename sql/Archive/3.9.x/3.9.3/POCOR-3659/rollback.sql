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
