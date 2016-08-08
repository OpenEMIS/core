--
-- POCOR-2489
--

-- security_functions
DELETE FROM `security_functions` WHERE  `id` = '6008';

-- db_patches
DELETE FROM `db_patches` WHERE  `issue` = 'POCOR-2489';
