-- POCOR-3080
DROP TABLE IF EXISTS `assessment_items_grading_types`;

DROP TABLE IF EXISTS `assessment_items`;

RENAME TABLE `z_3080_assessment_items` TO `assessment_items`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3080';


-- POCOR-2760
UPDATE `security_functions` SET _delete = 'Periods.remove' WHERE id = 5003;
-- SELECT * FROM `security_functions` WHERE id = 5003;

-- BACKING UP
UPDATE `security_role_functions`
    JOIN `z_2760_security_role_functions` ON z_2760_security_role_functions.id = security_role_functions.id
        SET security_role_functions._delete = z_2760_security_role_functions._delete;

DROP TABLE z_2760_security_role_functions;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2760';


-- POCOR-3049
-- security_functions
DELETE FROM `security_functions` WHERE `id`='5043';

UPDATE `security_functions` SET `order` = `order`-1 WHERE `order` >= 5029 AND `order` <= 5043;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3049';


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
