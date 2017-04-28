-- `system_patches`
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3699', NOW());

-- `qualification_titles`
DROP TABLE IF EXISTS `qualification_titles`;
CREATE TABLE IF NOT EXISTS `qualification_titles` (
  `id` int(11) NOT NULL,
  `name` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `qualification_level_id` int(11) NULL COMMENT 'links to qualification_levels.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the titles of the qualifications';

ALTER TABLE `qualification_titles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qualification_level_id` (`qualification_level_id`);

ALTER TABLE `qualification_titles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

# populate `qualification_titles` base on staff_qualifications table
INSERT INTO `qualification_titles` (`name`, `qualification_level_id`, `order`, `created_user_id`, `created`)
SELECT DISTINCT TRIM(qualification_title), qualification_level_id, 1, 1, now()
FROM `staff_qualifications`;

UPDATE `qualification_titles`
SET `order` = `id`;


-- `staff_qualifications_subjects`
DROP TABLE IF EXISTS `staff_qualifications_subjects`;
CREATE TABLE IF NOT EXISTS `staff_qualifications_subjects` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_qualification_id` int(11) NOT NULL COMMENT 'links to staff_qualifications.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the subjects that can be taught by teachers';

ALTER TABLE `staff_qualifications_subjects`
  ADD PRIMARY KEY (`staff_qualification_id`, `education_subject_id`),
  ADD KEY `staff_qualification_id` (`staff_qualification_id`),
  ADD KEY `education_subject_id` (`education_subject_id`);


# populate base on `staff_qualifications` table
INSERT INTO `staff_qualifications_subjects`
SELECT sha2(CONCAT(`A`.`id`, ',', `B`.`education_subject_id`), '256'), `A`.`id`, `B`.`education_subject_id`
FROM `staff_qualifications` `A`
INNER JOIN `qualification_specialisation_subjects` `B`
  ON `B`.`qualification_specialisation_id` = `A`.`qualification_specialisation_id`;


-- `staff_qualifications_temp`
DROP TABLE IF EXISTS `staff_qualifications_temp`;
CREATE TABLE IF NOT EXISTS `staff_qualifications_temp` (
  `id` int(11) NOT NULL,
  `document_no` varchar(100) DEFAULT NULL,
  `graduate_year` int(4) DEFAULT NULL,
  `qualification_institution` varchar(255) NOT NULL,
  `gpa` varchar(5) DEFAULT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `qualification_title_id` int(11) NOT NULL COMMENT 'links to qualification_titles.id',
  `qualification_country_id` int(11) DEFAULT NULL COMMENT 'links to countries.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `staff_qualifications_temp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `qualification_title_id` (`qualification_title_id`),
  ADD KEY `qualification_country_id` (`qualification_country_id`);

ALTER TABLE `staff_qualifications_temp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

# re-insert data
INSERT INTO `staff_qualifications_temp`
SELECT `A`.`id`, `A`.`document_no`, `A`.`graduate_year`, `B`.`name`, `A`.`gpa`, `A`.`file_name`, `A`.`file_content`, 
`A`.`staff_id`, `D`.`id`, `C`.`id`, `A`.`modified_user_id`, `A`.`modified`, `A`.`created_user_id`, `A`.`created`
FROM `staff_qualifications` `A`
LEFT JOIN `qualification_institutions` `B`
  ON `A`.`qualification_institution_id` = `B`.`id`
LEFT JOIN `countries` `C`
  ON `C`.`name` = `A`.`qualification_institution_country`
LEFT JOIN `qualification_titles` `D`
  ON (`D`.`qualification_level_id` = `A`.`qualification_level_id`
        AND TRIM(`D`.`name`) = TRIM(`A`.`qualification_title`));

-- backup old tables and rename new table.
RENAME TABLE `qualification_specialisations` TO `z_3699_qualification_specialisations`;
RENAME TABLE `qualification_specialisation_subjects` TO `z_3699_qualification_specialisation_subjects`;
RENAME TABLE `qualification_institutions` TO `z_3699_qualification_institutions`;
RENAME TABLE `staff_qualifications` TO `z_3699_staff_qualifications`;
RENAME TABLE `staff_qualifications_temp` TO `staff_qualifications`;

-- `labels`
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('5c3ddc98-0aec-11e7-b9c5-525400b263eb', 'Qualifications', 'file_content', 'Qualifications', 'Attachment', NULL, NULL, '1', NULL, NULL, '1', '2017-03-17 00:00:00');

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('a72ed550-1449-11e7-9f11-525400b263eb', 'Qualifications', 'education_subjects', 'Qualifications', 'Qualification Specialisation', NULL, NULL, '1', NULL, NULL, '1', '2017-03-29 00:00:00');