-- POCOR-3031
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3031', NOW());

-- remove orphan record
DELETE FROM `institution_staff_position_profiles`
WHERE NOT EXISTS (
        SELECT 1 FROM `institution_staff`
                WHERE `institution_staff`.`id` = `institution_staff_position_profiles`.`institution_staff_id`
        );


-- POCOR-2802
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2802', NOW());


-- code here
UPDATE `security_functions` SET _view = 'StudentFees.index' WHERE id = 2019;


-- POCOR-2781
-- db_patches
INSERT INTO db_patches (`issue`, `created`) VALUES ('POCOR-2781', NOW());

CREATE TABLE z_2781_institution_subject_staff LIKE institution_subject_staff;
INSERT INTO z_2781_institution_subject_staff SELECT * FROM institution_subject_staff;

DELETE FROM institution_subject_staff where `institution_subject_staff`.`status` = 0;

ALTER TABLE `institution_subject_staff` DROP `status`;


-- SELECT * FROM `security_functions` WHERE `name` LIKE '%subject%' AND `category` LIKE 'Staff - Career' AND controller = 'Staff';
-- add for institution staff subjects 3014
UPDATE security_functions SET `_add` = 'Subjects.add' WHERE id = 3014;

-- SELECT * FROM `security_functions` WHERE `name` LIKE '%classes%' AND `category` LIKE 'Staff - Career' AND controller = 'Staff';
-- add for institution staff classes 3013
UPDATE security_functions SET `_add` = 'Classes.add' WHERE id = 3013;


-- POCOR-2997
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2997', NOW());

-- backup the current table

ALTER TABLE `staff_training_needs`
RENAME TO `z_2997_staff_training_needs`;

-- create new table and apply the changes

CREATE TABLE IF NOT EXISTS `staff_training_needs` (
  `id` int(11) NOT NULL,
  `comments` text,
  `course_code` varchar(60) NULL,
  `course_name` varchar(250) NULL,
  `course_description` text,
  `course_id` int(11) NOT NULL COMMENT 'links to training_courses.id',
  `training_need_category_id` int(11) NOT NULL,
  `training_requirement_id` int(11) NOT NULL,
  `training_priority_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `staff_training_needs`
--
ALTER TABLE `staff_training_needs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `training_need_category_id` (`training_need_category_id`),
  ADD KEY `training_requirement_id` (`training_requirement_id`),
  ADD KEY `training_priority_id` (`training_priority_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `status_id` (`status_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `staff_training_needs`
--
ALTER TABLE `staff_training_needs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- reinsert from backup table

INSERT INTO `staff_training_needs`
SELECT `z_2997_staff_training_needs`.`id`,
  `z_2997_staff_training_needs`.`comments`,
  `z_2997_staff_training_needs`.`course_code`,
  `z_2997_staff_training_needs`.`course_name`,
  `z_2997_staff_training_needs`.`course_description`,
  `z_2997_staff_training_needs`.`course_id`,
  `z_2997_staff_training_needs`.`training_need_category_id`,
  `z_2997_staff_training_needs`.`training_requirement_id`,
  `z_2997_staff_training_needs`.`training_priority_id`,
  `z_2997_staff_training_needs`.`staff_id`,
  `z_2997_staff_training_needs`.`status_id`,
  `z_2997_staff_training_needs`.`modified_user_id`,
  `z_2997_staff_training_needs`.`modified`,
  `z_2997_staff_training_needs`.`created_user_id`,
  `z_2997_staff_training_needs`.`created`
FROM `z_2997_staff_training_needs`;


-- POCOR-2714
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2714', NOW());

-- backup countries table
CREATE TABLE z_2714_countries LIKE countries;
INSERT INTO z_2714_countries SELECT * from countries;

SET @fieldOptionOrder := 0;
SELECT field_options.order INTO @fieldOptionOrder FROM field_options WHERE code = 'Countries';
UPDATE field_options SET field_options.order = field_options.order+1 WHERE field_options.order > @fieldOptionOrder;
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
('FieldOption', 'Nationalities', 'Nationalities', 'Others', '{"model":"FieldOption.Nationalities"}', @fieldOptionOrder+1, 1, 1, NOW());

CREATE TABLE `nationalities` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `identity_type_id` int(11) DEFAULT NULL,
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `nationalities`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `nationalities` ADD INDEX(`identity_type_id`);

ALTER TABLE `nationalities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


INSERT INTO `nationalities` (
`id`,
`name`,
`identity_type_id`,
`order`,
`visible`,
`editable`,
`default`,
`international_code`,
`national_code`,
`modified_user_id`,
`modified`,
`created_user_id`,
`created`
)
SELECT
`id`,
`name`,
`identity_type_id`,
`order`,
`visible`,
1,
`default`,
`international_code`,
`national_code`,
`modified_user_id`,
`modified`,
`created_user_id`,
`created`
FROM countries
WHERE EXISTS (
    SELECT * FROM user_nationalities WHERE `country_id` = `countries`.`id`
);


-- Fix: re-create the user_nationalities table with the correct columns instead of altering table as it might take a long time
-- backing up
RENAME TABLE user_nationalities TO z_2714_user_nationalities;

-- new table user_nationalities START
CREATE TABLE IF NOT EXISTS `user_nationalities` (
  `id` int(11) NOT NULL,
  `nationality_id` int(11) NOT NULL,
  `comments` text,
  `security_user_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `user_nationalities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nationality_id` (`nationality_id`),
  ADD KEY `security_user_id` (`security_user_id`);

ALTER TABLE `user_nationalities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
-- new table user_nationalities END

INSERT INTO `user_nationalities` (
    `id`,
    `nationality_id`,
    `comments`,
    `security_user_id`,
    `modified_user_id`,
    `modified`,
    `created_user_id`,
    `created`
) SELECT
    `id`,
    `country_id`,
    `comments`,
    `security_user_id`,
    `modified_user_id`,
    `modified`,
    `created_user_id`,
    `created`
    FROM z_2714_user_nationalities;


-- NEED TO DROP THE IDENTITY_TYPE COLUMN FOR COUNTRIES
ALTER TABLE `countries` DROP `identity_type_id`;


-- 3.5.6
UPDATE config_items SET value = '3.5.6' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
