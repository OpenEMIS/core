UPDATE `navigations` SET `is_wizard` = 0 WHERE `plugin` LIKE 'Staff' AND `controller` LIKE 'Staff' AND `action` LIKE 'bankAccounts';

UPDATE `navigations` SET `is_wizard` = 0 WHERE `plugin` LIKE 'Students' AND `controller` LIKE 'Students' AND `action` LIKE 'bankAccounts';