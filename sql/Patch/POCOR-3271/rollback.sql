-- `institution_genders`
DROP TABLE IF EXISTS `institution_genders`;
RENAME TABLE `z_3271_institution_genders` TO `institution_genders`;

UPDATE institutions
JOIN z_3271_institution_genders
    ON z_3271_institution_genders.national_code = institutions.institution_gender_id
SET institutions.institution_gender_id = z_3271_institution_genders.id;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3271';
