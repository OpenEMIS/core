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






