-- `institution_genders`
DROP TABLE IF EXISTS `institution_genders`;
RENAME TABLE `z_3271_institution_genders` TO `institution_genders`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3271';
