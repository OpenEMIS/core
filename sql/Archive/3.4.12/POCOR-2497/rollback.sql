--
-- POCOR-2497
--

-- security_functions
UPDATE `security_functions` SET `_view` = 'StaffSubjects.index' WHERE `security_functions`.`id` = 7023;
DELETE FROM `security_functions` WHERE `id` IN ('7045', '7046');

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2497';