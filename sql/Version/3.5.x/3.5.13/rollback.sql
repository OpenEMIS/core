-- POCOR-2602
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2602';

-- field_options
DELETE FROM `field_options`
WHERE `plugin` = 'Institution'
AND `code` = 'ShiftOptions'
AND `name` = 'Shift Options'
AND `parent` = 'Institution';

-- shift_options
DROP TABLE IF EXISTS `shift_options`;

-- institution_shifts
DROP TABLE IF EXISTS `institution_shifts`;
RENAME TABLE `z_2602_institution_shifts` TO `institution_shifts`;

-- institutions
DROP TABLE IF EXISTS `institutions`;
RENAME TABLE `z_2602_institutions` TO `institutions`;


-- 3.5.12
UPDATE config_items SET value = '3.5.12' WHERE code = 'db_version';
