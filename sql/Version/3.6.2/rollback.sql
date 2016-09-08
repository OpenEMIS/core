-- POCOR-3303
-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.excel' WHERE `id` = 1015;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3303';


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


-- POCOR-3264
-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3264';


-- Restore tables
DROP TABLE IF EXISTS `infrastructure_levels`;
RENAME TABLE `z_3017_infrastructure_levels` TO `infrastructure_levels`;

DROP TABLE IF EXISTS `infrastructure_types`;
RENAME TABLE `z_3017_infrastructure_types` TO `infrastructure_types`;

DROP TABLE IF EXISTS `room_statuses`;
DROP TABLE IF EXISTS `room_types`;
DROP TABLE IF EXISTS `institution_rooms`;

-- custom field
DROP TABLE IF EXISTS `infrastructure_custom_forms`;
RENAME TABLE `z_3017_infrastructure_custom_forms` TO `infrastructure_custom_forms`;

DROP TABLE IF EXISTS `infrastructure_custom_forms_filters`;
RENAME TABLE `z_3017_infrastructure_custom_forms_filters` TO `infrastructure_custom_forms_filters`;

DROP TABLE IF EXISTS `infrastructure_custom_field_values`;
RENAME TABLE `z_3017_infrastructure_custom_field_values` TO `infrastructure_custom_field_values`;

DROP TABLE IF EXISTS `institution_infrastructures`;
RENAME TABLE `z_3017_institution_infrastructures` TO `institution_infrastructures`;

RENAME TABLE `z_3017_infrastructure_custom_table_columns` TO `infrastructure_custom_table_columns`;
RENAME TABLE `z_3017_infrastructure_custom_table_rows` TO `infrastructure_custom_table_rows`;
RENAME TABLE `z_3017_infrastructure_custom_table_cells` TO `infrastructure_custom_table_cells`;
DROP TABLE IF EXISTS `room_custom_field_values`;

-- custom_modules
DROP TABLE IF EXISTS `custom_modules`;
RENAME TABLE `z_3017_custom_modules` TO `custom_modules`;

-- security_functions
UPDATE `security_functions`
SET `_view` = 'Fields.index|Fields.view|Pages.index|Pages.view|Levels.index|Levels.view|Types.index|Types.view', `_edit` = 'Fields.edit|Pages.edit|Levels.edit|Types.edit', `_add` = 'Fields.add|Pages.add|Levels.add|Types.add', `_delete` = 'Fields.remove|Pages.remove|Levels.remove|Types.remove'
WHERE id = 5018;

UPDATE `security_functions`
SET `_view` = 'Infrastructures.index|Infrastructures.view', `_edit` = 'Infrastructures.edit', `_add` = 'Infrastructures.add', `_delete` = 'Infrastructures.remove'
WHERE id = 1011;

-- labels
DELETE FROM `labels` WHERE `module` = 'InstitutionRooms' AND `field` = 'institution_infrastructure_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3017';


-- 3.6.1
UPDATE config_items SET value = '3.6.1' WHERE code = 'db_version';
