-- POCOR-2498
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-2498', NOW());

-- code here
-- Table structure for table `indexes`
CREATE TABLE IF NOT EXISTS `indexes` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `indexes`
    ADD KEY `academic_period_id` (`academic_period_id`),
    ADD KEY `modified_user_id` (`modified_user_id`),
    ADD KEY `created_user_id` (`created_user_id`);

-- Table structure for table `institution_indexes`
CREATE TABLE IF NOT EXISTS `institution_indexes` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `status` INT(2) NOT NULL DEFAULT '1' COMMENT '1 => Not Generated 2 => Processing 3 => Completed 4 => Not Completed',
    `pid` INT(11) DEFAULT NULL,
    `generated_on` datetime DEFAULT NULL,
    `generated_by` int(11) DEFAULT NULL COMMENT 'links to security_users.id',
    `index_id` int(11) NOT NULL COMMENT 'links to indexes.id',
    `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `institution_indexes`
    ADD KEY `index_id` (`index_id`),
    ADD KEY `institution_id` (`institution_id`),
    ADD KEY `modified_user_id` (`modified_user_id`),
    ADD KEY `created_user_id` (`created_user_id`);

-- Table structure for table `indexes_criterias`
CREATE TABLE IF NOT EXISTS `indexes_criterias` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `criteria` varchar(50) NOT NULL,
    `operator` int(3) NOT NULL,
    `threshold` int(3) NOT NULL,
    `index_value` int(2) NOT NULL,
    `index_id` int(3) NOT NULL COMMENT 'links to indexes.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `indexes_criterias`
    ADD KEY `index_id` (`index_id`),
    ADD KEY `modified_user_id` (`modified_user_id`),
    ADD KEY `created_user_id` (`created_user_id`);

-- Table structure for table `behaviour_classifications`
CREATE TABLE IF NOT EXISTS `behaviour_classifications` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `order` int(3) NOT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `editable` int(1) NOT NULL DEFAULT '1',
    `default` int(1) NOT NULL DEFAULT '0',
    `international_code` varchar(50) DEFAULT NULL,
    `national_code` varchar(50) DEFAULT NULL,
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adding new column student_behaviour_categories
ALTER TABLE `student_behaviour_categories` ADD `behaviour_classification_id` INT(11) NOT NULL COMMENT 'links to behaviour_classifications.id' AFTER `national_code`;
ALTER TABLE `student_behaviour_categories`
    ADD KEY `behaviour_classification_id` (`behaviour_classification_id`),
    ADD KEY `modified_user_id` (`modified_user_id`),
    ADD KEY `created_user_id` (`created_user_id`);

-- Table structure for table `institution_student_indexes`
CREATE TABLE IF NOT EXISTS `institution_student_indexes` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `average_index` decimal(4,2) NOT NULL,
    `total_index` int(3) NOT NULL,
    `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
    `index_id` int(11) NOT NULL COMMENT 'links to indexes.id',
    `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
    `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `institution_student_indexes`
    ADD KEY `academic_period_id` (`academic_period_id`),
    ADD KEY `index_id` (`index_id`),
    ADD KEY `institution_id` (`institution_id`),
    ADD KEY `student_id` (`student_id`),
    ADD KEY `modified_user_id` (`modified_user_id`),
    ADD KEY `created_user_id` (`created_user_id`);

-- Table structure for table `student_indexes_criterias`
CREATE TABLE IF NOT EXISTS `student_indexes_criterias` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `value` varchar(50) DEFAULT NULL,
    `institution_student_index_id` int(11) NOT NULL COMMENT 'links to institution_student_indexes.id',
    `indexes_criteria_id` int(11) NOT NULL COMMENT 'links to indexes_criterias.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `student_indexes_criterias`
    ADD KEY `institution_student_index_id` (`institution_student_index_id`),
    ADD KEY `indexes_criteria_id` (`indexes_criteria_id`),
    ADD KEY `modified_user_id` (`modified_user_id`),
    ADD KEY `created_user_id` (`created_user_id`);

-- Student behaviours
ALTER TABLE `student_behaviours`
    ADD `academic_period_id` INT(11) DEFAULT NULL COMMENT 'links to academic_periods.id' AFTER `time_of_behaviour`,
    ADD KEY `academic_period_id` (`academic_period_id`);

UPDATE `student_behaviours`
    SET `academic_period_id` = (
        SELECT `id` FROM `academic_periods`
        WHERE `start_date` <= `student_behaviours`.`date_of_behaviour`
        AND `end_date` >= `student_behaviours`.`date_of_behaviour`
    )
    WHERE `academic_period_id` = 0;

-- Security functions (permission)
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
    VALUES ('1055', 'Indexes', 'Institutions', 'Institutions', 'Students', '8', 'Indexes.index|Indexes.view|InstitutionStudentIndexes.index|InstitutionStudentIndexes.view', NULL, NULL, NULL, 'Indexes.generate', '1055', '1', NULL, NULL, NULL, '1', '2015-08-04 02:41:00'),
        ('2032', 'Indexes', 'Institutions', 'Institutions', 'Students - Academic', '2000', 'StudentIndexes.index|StudentIndexes.view', NULL, NULL, NULL, NULL, '2032', '1', NULL, NULL, NULL, '1', '2015-08-04 02:41:00'),
        ('5066', 'Indexes', 'Indexes', 'Administration', 'Indexes', '5000', 'Indexes.index|Indexes.view', 'Indexes.edit', 'Indexes.add', 'Indexes.remove', NULL, '5066', '1', NULL, NULL, NULL, '1', '2015-08-04 02:41:00');


-- POCOR-3644
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3644', NOW());

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 2018 AND `order` < 3000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('2031', 'Textbooks', 'Institutions', 'Institutions', 'Students - Academic', '2000', 'StudentTextbooks.index|StudentTextbooks.view', NULL, NULL, NULL, NULL, '2018', '1', NULL, NULL, NULL, '1', '2017-02-14 00:00:00');

UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 7019 AND `order` < 8000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('7051', 'Textbooks', 'Directories', 'Directory', 'Students - Academic', '7000', 'StudentTextbooks.index|StudentTextbooks.view', NULL, NULL, NULL, NULL, '7019', '1', NULL, NULL, NULL, '1', '2017-02-21 00:00:00');


-- POCOR-3797
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3797', NOW());

-- competency_criterias
RENAME TABLE `competency_criterias` TO `z_3797_competency_criterias`;

DROP TABLE IF EXISTS `competency_criterias`;
CREATE TABLE `competency_criterias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_item_id` int(11) NOT NULL COMMENT 'links to competency_items.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `competency_grading_type_id` int(11) NOT NULL COMMENT 'links to competency_grading_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`,`academic_period_id`,`competency_item_id`,`competency_template_id`),
  KEY `id` (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `competency_item_id` (`competency_item_id`),
  KEY `competency_template_id` (`competency_template_id`),
  KEY `competency_grading_type_id` (`competency_grading_type_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of competency criterias for a given competency item';

INSERT INTO `competency_criterias` (`id`, `code`, `name`, `academic_period_id`, `competency_item_id`, `competency_template_id`, `competency_grading_type_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, NULL, `name`, `academic_period_id`, `competency_item_id`, `competency_template_id`, `competency_grading_type_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_3797_competency_criterias`;


-- POCOR-3772
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3772', NOW());

-- security_group_areas
DROP TABLE IF EXISTS `z_3772_security_group_areas`;
CREATE TABLE `z_3772_security_group_areas` LIKE `security_group_areas`;

INSERT `z_3772_security_group_areas` SELECT * FROM `security_group_areas`;

DELETE FROM `security_group_areas`
WHERE NOT EXISTS (
        SELECT 1 FROM `security_groups`
        WHERE `security_groups`.`id` = `security_group_areas`.`security_group_id`
);

-- security_group_institutions
DROP TABLE IF EXISTS `z_3772_security_group_institutions`;
CREATE TABLE `z_3772_security_group_institutions` LIKE `security_group_institutions`;

INSERT `z_3772_security_group_institutions` SELECT * FROM `security_group_institutions`;

DELETE FROM `security_group_institutions`
WHERE NOT EXISTS (
    SELECT 1
    FROM `security_groups`
    WHERE `security_groups`.`id` = `security_group_institutions`.`security_group_id`
);

-- security_group_users
DROP TABLE IF EXISTS `z_3772_security_group_users`;
CREATE TABLE `z_3772_security_group_users` LIKE `security_group_users`;

INSERT `z_3772_security_group_users` SELECT * FROM `security_group_users`;

DELETE FROM `security_group_users`
WHERE NOT EXISTS (
    SELECT 1
    FROM `security_groups`
    WHERE `security_groups`.`id` = `security_group_users`.`security_group_id`
);


-- POCOR-3692
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3692', NOW());

-- examination_item_results
CREATE TABLE `z_3692_examination_item_results` LIKE `examination_item_results`;
INSERT INTO `z_3692_examination_item_results`
SELECT * FROM `examination_item_results`;

DELETE FROM `examination_item_results`
WHERE `marks` IS NULL
AND `examination_grading_option_id` IS NULL;


-- POCOR-3717
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3717', NOW());

-- config_item_options
UPDATE `config_item_options` SET `value` = 'jS F Y' WHERE `config_item_options`.`id` = 7;


-- 3.9.4
UPDATE config_items SET value = '3.9.4' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
