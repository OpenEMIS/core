-- POCOR-3923
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3923', NOW());

-- contact_options
RENAME TABLE `contact_options` TO `z_3923_contact_options`;

DROP TABLE IF EXISTS `contact_options`;
CREATE TABLE IF NOT EXISTS `contact_options` (
  `id` int(11) NOT NULL,
  `name` varchar(10) NOT NULL,
  `code` varchar(10) NOT NULL,
  `order` int(11) NOT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contain the options of contact used by contact type';

ALTER TABLE `contact_options`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `contact_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

INSERT INTO `contact_options`
SELECT `id`,`name`, UPPER(`name`), `order`, 1, NOW()
FROM `z_3923_contact_options`;

-- security_users
DROP TABLE IF EXISTS `z_3923_security_users`;
CREATE TABLE IF NOT EXISTS `z_3923_security_users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains all user information';

ALTER TABLE `z_3923_security_users`
  ADD PRIMARY KEY (`id`);

INSERT INTO `z_3923_security_users`
SELECT `id`,`email`
FROM `security_users`
WHERE `email` IS NOT NULL;

UPDATE `security_users`
SET `email` = NULL;

UPDATE `security_users` `SU`
INNER JOIN `user_contacts` `UC` ON `UC`.`security_user_id` = `SU`.`id`
INNER JOIN `contact_types` `CT` ON `UC`.`contact_type_id` = `CT`.`id`
INNER JOIN `contact_options` `CO` ON (`CO`.`id` = `CT`.`contact_option_id` AND `CO`.`code` = 'EMAIL')
SET `SU`.`email` = `UC`.`value`;


-- POCOR-3996
-- `db_patches`
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3996', NOW());

-- `import_mapping`
INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES
('Training.TrainingSessionsTrainees', 'identity_number', '', 3, 0, NULL, NULL, NULL),
('Training.TrainingSessionsTrainees', 'identity_type_id', 'Code (Optional)', 2, 1, 'FieldOption', 'IdentityTypes', 'national_code'),
('Training.TrainingSessionsTrainees', 'openemis_no', '(Optional)', 1, 0, NULL, NULL, NULL);

-- `labels`
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('6c3d2497-4b27-11e7-9846-525400b263eb', 'TrainingSessionsTrainees', 'openemis_no', 'Administration > Training > Sessions > Trainees', 'OpenEMIS ID', NULL, NULL, '1', NULL, NULL, '1', '2017-06-07 00:00:00');


-- POCOR-3983
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3983', NOW());


-- institution_statuses
RENAME TABLE `institution_statuses` TO `z_3983_institution_statuses`;

DROP TABLE IF EXISTS `institution_statuses`;
CREATE TABLE IF NOT EXISTS `institution_statuses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(100) NOT NULL,
    `name` varchar(250) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This is a field option table containing the list of user-defined statuses used by institutions';

-- insert value to institution_statuses
INSERT INTO `institution_statuses` (`id`, `code`, `name`)
VALUES  (1, 'ACTIVE', 'Active'),
        (2, 'INACTIVE', 'Inactive');

-- institutions
RENAME TABLE `institutions` TO `z_3983_institutions`;
CREATE TABLE `institutions` LIKE `z_3983_institutions`;

INSERT INTO `institutions` (`id`, `name`, `alternative_name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `date_opened`, `year_opened`, `date_closed`, `year_closed`, `longitude`, `latitude`, `shift_type`, `classification`, `area_id`, `area_administrative_id`, `institution_locality_id`, `institution_type_id`, `institution_ownership_id`, `institution_status_id`, `institution_sector_id`, `institution_provider_id`, `institution_gender_id`, `institution_network_connectivity_id`, `security_group_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `alternative_name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `date_opened`, `year_opened`, `date_closed`, `year_closed`, `longitude`, `latitude`, `shift_type`, `classification`, `area_id`, `area_administrative_id`, `institution_locality_id`, `institution_type_id`, `institution_ownership_id`, (IF(`date_closed` IS NULL OR `date_closed` >= CURDATE(), 1, 2)), `institution_sector_id`, `institution_provider_id`, `institution_gender_id`, `institution_network_connectivity_id`, `security_group_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3983_institutions`;

-- import_mapping
CREATE TABLE `z_3983_import_mapping` LIKE `import_mapping`;
INSERT `z_3983_import_mapping` SELECT * FROM `import_mapping`;

DELETE FROM `import_mapping` WHERE `model` = 'Institution.Institutions' AND `column_name` = 'institution_status_id';
UPDATE `import_mapping` SET `id` = `id` - 1, `order` = `order` - 1 WHERE `model` = 'Institution.Institutions' AND `id` > 20;


-- POCOR-1330
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-1330', NOW());

-- education_stages
DROP TABLE IF EXISTS `education_stages`;
CREATE TABLE IF NOT EXISTS `education_stages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(150) NOT NULL,
    `code` varchar(20) NOT NULL,
    `order` int(3) NOT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the educational absolute grades';

-- insert value to education_stages
SET @order := 0;

INSERT INTO `education_stages` (`name`, `code`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT DISTINCT `name`, `code`, @order := @order + 1, 1, NULL, NULL, 1, NOW()
FROM `education_grades`;

-- education_grades
RENAME TABLE `education_grades` TO `z_1330_education_grades`;

DROP TABLE IF EXISTS `education_grades`;
CREATE TABLE IF NOT EXISTS `education_grades` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
    `admission_age` int(3) NOT NULL,
    `order` int(3) NOT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `education_stage_id` int(11) NOT NULL COMMENT 'links to education_stages.id',
    `education_programme_id` int(11) NOT NULL COMMENT 'links to education_programmes.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `education_stage_id` (`education_stage_id`),
    KEY `education_programme_id` (`education_programme_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of education grades linked to specific education programmes';

INSERT INTO `education_grades` (`id`, `code`, `name`, `admission_age`, `order`, `visible`, `education_stage_id`, `education_programme_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Z`.`id`, `Z`.`code`, `Z`.`name`, `Z`.`admission_age`, `Z`.`order`, `Z`.`visible`, `ES`.`id`, `Z`.`education_programme_id`, `Z`.`modified_user_id`, `Z`.`modified`, `Z`.`created_user_id`, `Z`.`created`
FROM `z_1330_education_grades` AS `Z`
INNER JOIN `education_stages` AS `ES`
ON `ES`.`code` = `Z`.`code` AND `ES`.`name` = `Z`.`name`;

-- security_functions
CREATE TABLE `z_1330_security_functions` LIKE `security_functions`;
INSERT `z_1330_security_functions` SELECT * FROM `security_functions`;

UPDATE `security_functions`
SET `_view` = 'Stages.index|Stages.view|Subjects.index|Subjects.view|Certifications.index|Certifications.view|FieldOfStudies.index|FieldOfStudies.view|ProgrammeOrientations.index|ProgrammeOrientations.view',
    `_edit` = 'Stages.edit|Subjects.edit|Certifications.edit|FieldOfStudies.edit|ProgrammeOrientations.edit',
    `_add` = 'Stages.add|Subjects.add|Certifications.add|FieldOfStudies.add|ProgrammeOrientations.add',
    `_delete` = 'Stages.remove|Subjects.remove|Certifications.remove|FieldOfStudies.remove|ProgrammeOrientations.remove'
WHERE `security_functions`.`id` = 5009 ;


-- 3.10.2
UPDATE config_items SET value = '3.10.2' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
