UPDATE `navigations` SET `action` = 'positions', `pattern` = 'positions' WHERE `controller` = 'Staff' AND `title` = 'Positions';

SET @ordering := 0;
SELECT `order` into @ordering FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Positions' AND `category` = 'Details';
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `security_functions` WHERE `controller` = 'Staff' AND `name` = 'Positions' AND `category` = 'Details';
