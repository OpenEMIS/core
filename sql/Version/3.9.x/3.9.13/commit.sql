-- POCOR-3823
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3823', NOW());

-- import_mapping
DELETE FROM `import_mapping`
WHERE `model` = 'User.Users'
AND `column_name` = 'Identity';

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES (NULL, 'User.Users', 'nationality_id', 'Id (Optional)', '14', '2', 'FieldOption', 'Nationalities', 'id');

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES (NULL, 'User.Users', 'identity_type_id', 'Code (Optional)', '15', '1', 'FieldOption', 'IdentityTypes', 'national_code');

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES (NULL, 'User.Users', 'identity_number', '', '16', '0', NULL, NULL, NULL);


-- POCOR-3801
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3801', NOW());

-- security_functions
UPDATE `security_functions` SET `_view` = 'Students.index|Students.view' WHERE `id` = 1012;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(2033, 'Surveys', 'Students', 'Institutions', 'Students - General', 2000, 'StudentSurveys.index|StudentSurveys.view', NULL, NULL, NULL, NULL, 2033, 1, NULL, NULL, NULL, 1, NOW());


-- POCOR-3936
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3936', NOW());

-- staff_behaviours
RENAME TABLE `staff_behaviours` TO `z_3936_staff_behaviours`;

DROP TABLE IF EXISTS `staff_behaviours`;
CREATE TABLE IF NOT EXISTS `staff_behaviours` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `description` text NOT NULL,
    `date_of_behaviour` date NOT NULL,
    `time_of_behaviour` time DEFAULT NULL,
    `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
    `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
    `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
    `staff_behaviour_category_id` int(11) NOT NULL COMMENT 'links to staff_behaviour_categories.id',
    `behaviour_classification_id` int(11) NOT NULL COMMENT 'links to behaviour_classifications.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `academic_period_id` (`academic_period_id`),
    KEY `staff_id` (`staff_id`),
    KEY `institution_id` (`institution_id`),
    KEY `staff_behaviour_category_id` (`staff_behaviour_category_id`),
    KEY `behaviour_classification_id` (`behaviour_classification_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all behavioural records of staff';

-- insert value to the staff_behaviours table from backup staff_behaviours
INSERT INTO `staff_behaviours` (`id`, `description`, `date_of_behaviour`, `time_of_behaviour`, `academic_period_id`, `staff_id`, `institution_id`, `staff_behaviour_category_id`, `behaviour_classification_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Z`.`id`, `Z`.`description`, `Z`.`date_of_behaviour`, `Z`.`time_of_behaviour`, `AP`.`id`, `Z`.`staff_id`, `Z`.`institution_id`, `Z`.`staff_behaviour_category_id`, `Z`.`behaviour_classification_id`, `Z`.`modified_user_id`, `Z`.`modified`, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3936_staff_behaviours` AS `Z`
INNER JOIN `academic_periods` AS `AP`
ON `AP`.`start_date` <= `Z`.`date_of_behaviour` AND `AP`.`end_date` >= `Z`.`date_of_behaviour`
INNER JOIN `academic_period_levels` AS `APL`
ON `APL`.`id` = `AP`.`academic_period_level_id`
AND `APL`.`level` = 1;


-- POCOR-3880
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3880', NOW());

DELIMITER $$

DROP PROCEDURE IF EXISTS addRoleMapping

$$
CREATE PROCEDURE addRoleMapping()
BEGIN
    DECLARE checkOAuthExists INT(11);
    DECLARE checkSamlExists INT(11);
    SET checkOAuthExists = 0;
    SET checkSamlExists = 0;

    SELECT COUNT(*) INTO checkOAuthExists FROM authentication_type_attributes WHERE authentication_type = 'OAuth2OpenIDConnect';
    SELECT COUNT(*) INTO checkSamlExists FROM authentication_type_attributes WHERE authentication_type = 'Saml2';

    IF checkOAuthExists > 0 THEN
        INSERT INTO authentication_type_attributes (id, authentication_type, attribute_field, attribute_name, `value`, created, created_user_id) VALUES (uuid(), 'OAuth2OpenIDConnect', 'role_mapping', 'Role Mapping', '', NOW(), 1);
    END IF;
    IF checkSamlExists > 0 THEN
        INSERT INTO authentication_type_attributes (id, authentication_type, attribute_field, attribute_name, `value`, created, created_user_id) VALUES (uuid(), 'Saml2', 'saml_role_mapping', 'Role Mapping', '', NOW(), 1);
    END IF;
END;
$$

CALL addRoleMapping
$$

DROP PROCEDURE IF EXISTS addRoleMapping
$$

DELIMITER ;


-- 3.9.13
UPDATE config_items SET value = '3.9.13' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
