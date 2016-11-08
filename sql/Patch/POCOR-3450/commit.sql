-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3450', NOW());

-- code here
CREATE TABLE IF NOT EXISTS `staff_appraisals` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(100) NOT NULL,
    `from` date NOT NULL,
    `to` date NOT NULL,
    `final_rating` DECIMAL(4,2) NOT NULL, -- 4 is the max digits, 2 is the max digits after the decimal point
    `comment` text DEFAULT NULL,
    `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
    `competency_set_id` int(11) NOT NULL COMMENT 'links to competency_sets.id',
    `staff_appraisal_type_id` int(11) NOT NULL COMMENT 'links to staff_appraisal_types.id',
    `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `staff_appraisals`
    ADD INDEX `academic_period_id` (`academic_period_id`),
    ADD INDEX `competency_set_id` (`competency_set_id`),
    ADD INDEX `staff_appraisal_type_id` (`staff_appraisal_type_id`),
    ADD INDEX `staff_id` (`staff_id`);


CREATE TABLE IF NOT EXISTS `staff_appraisal_types` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `code` VARCHAR(100) NOT NULL,
    `name` VARCHAR(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `staff_appraisal_types` (`code`, `name`)
VALUES  ('SELF', 'Self'),
                ('SUPERVISOR', 'Supervisor'),
                ('PEER', 'Peer');


CREATE TABLE IF NOT EXISTS `competencies` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(55) NOT NULL,
    `order` int(3) NOT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `editable` int(1) NOT NULL DEFAULT '1',
    `default` int(1) NOT NULL DEFAULT '0',
    `min` DECIMAL(4,2) NOT NULL DEFAULT '0',
    `max` DECIMAL(4,2) NOT NULL DEFAULT '10',
    `international_code` VARCHAR(50) DEFAULT NULL,
    `national_code` VARCHAR(50) DEFAULT NULL,
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `competency_sets` (
    `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `order` int(3) NOT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `editable` int(1) NOT NULL DEFAULT '1',
    `default` int(1) NOT NULL DEFAULT '0',
    `international_code` VARCHAR(50) DEFAULT NULL,
    `national_code` VARCHAR(50) DEFAULT NULL,
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `competency_sets_competencies` (
    `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `competency_id` int(11) NOT NULL COMMENT 'links to competencies.id',
    `competency_set_id` int(11) NOT NULL COMMENT 'links to competency_sets.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `competency_sets_competencies`
    ADD PRIMARY KEY (`competency_id`, `competency_set_id`),
    ADD UNIQUE KEY `id` (`id`),
    ADD KEY `competency_id` (`competency_id`),
    ADD KEY `competency_set_id` (`competency_set_id`);


CREATE TABLE IF NOT EXISTS `staff_appraisals_competencies` (
    `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
    `competency_id` int(11) NOT NULL COMMENT 'links to competencies.id',
    `staff_appraisal_id` int(11) NOT NULL COMMENT 'links to staff_appraisals.id',
    `rating` DECIMAL(4,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `staff_appraisals_competencies`
    ADD PRIMARY KEY (`competency_id`, `staff_appraisal_id`),
    ADD UNIQUE KEY `id` (`id`),
    ADD KEY `competency_id` (`competency_id`),
    ADD KEY `staff_appraisal_id` (`staff_appraisal_id`);


-- security_function (permission)
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `id` BETWEEN 3000 AND 4000 AND `order` >= 3025;
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `id` BETWEEN 7000 AND 8000 AND `order` >= 7033;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('3037', 'Appraisals', 'Institutions', 'Institutions', 'Staff - Professional Development', '3000', 'StaffAppraisals.index|StaffAppraisals.view', 'StaffAppraisals.edit', 'StaffAppraisals.add', 'StaffAppraisals.remove', NULL, '3025', '1', NULL, NULL, NULL, '1', NOW()),
        ('7049', 'Appraisals', 'Directories', 'Directory', 'Staff - Professional Development', '7000', 'StaffAppraisals.index|StaffAppraisals.view', NULL, NULL, NULL, NULL, '7033', '1', NULL, NULL, NULL, '1', NOW());


