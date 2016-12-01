-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2498', NOW());

-- code here
-- Table structure for table `indexes`
CREATE TABLE IF NOT EXISTS `indexes` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `generated_by` int(11) DEFAULT NULL,
    `generated_on` datetime DEFAULT NULL,
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `indexes_criterias`
    ADD KEY `index_id` (`index_id`);


-- Table structure for table `classifications`
CREATE TABLE IF NOT EXISTS `classifications` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Adding new column student_behaviour_categories
ALTER TABLE `student_behaviour_categories` ADD `classification_id` INT(3) NOT NULL DEFAULT '0' COMMENT 'links to classification.id' AFTER `national_code`;
ALTER TABLE `student_behaviour_categories` ADD KEY `classification_id` (`classification_id`);


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `institution_student_indexes`
    ADD KEY `academic_period_id` (`academic_period_id`),
    ADD KEY `index_id` (`index_id`),
    ADD KEY `institution_id` (`institution_id`),
    ADD KEY `student_id` (`student_id`);


-- Table structure for table `student_indexes_criterias`
CREATE TABLE IF NOT EXISTS `student_indexes_criterias` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `value` varchar(50) NOT NULL,
    `institution_student_index_id` int(11) NOT NULL COMMENT 'links to institution_student_indexes.id',
    `indexes_criteria_id` int(11) NOT NULL COMMENT 'links to indexes_criterias.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `student_indexes_criterias`
    ADD KEY `institution_student_index_id` (`institution_student_index_id`),
    ADD KEY `indexes_criteria_id` (`indexes_criteria_id`);













