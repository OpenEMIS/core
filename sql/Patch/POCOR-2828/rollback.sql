-- security_functions
UPDATE `security_functions` SET `_add`='StaffUser.add' WHERE `id`='1044';
UPDATE `security_functions` SET `_add`='Staff.add' WHERE `id`='1016';

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-2828';
