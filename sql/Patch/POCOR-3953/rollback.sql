-- guidance_types
DROP TABLE `guidance_types`;

-- institution_counselors
DROP TABLE `institution_counselors`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3953';
