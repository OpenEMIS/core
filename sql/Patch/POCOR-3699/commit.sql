-- `system_patches`
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3699', NOW());

-- backup old tables
RENAME TABLE `qualification_specialisations` TO `z_3699_qualification_specialisations`;
RENAME TABLE `qualification_specialisation_subjects` TO `z_3699_qualification_specialisation_subjects`;
RENAME TABLE `qualification_institutions` TO `z_3699_qualification_institutions`;
RENAME TABLE `qualification_levels` TO `z_3699_qualification_levels`;
RENAME TABLE `staff_qualifications` TO `z_3699_staff_qualifications`;

-- `qualification_levels`
#ensure that there is at least one default qualification_levels
-- DROP PROCEDURE IF EXISTS `ensureDefaultQualificationLevel`;

-- DELIMITER $$
-- CREATE PROCEDURE `ensureDefaultQualificationLevel` (OUT defaultQualificationLevelId int)
-- BEGIN
--     DECLARE recordCount INT;
--     DECLARE defaultId INT;

--     SELECT COUNT(*) INTO recordCount 
--     FROM `qualification_levels`;

--     SELECT `id` INTO defaultId 
--     FROM `qualification_levels`
--     WHERE `default` = 1;

--     SET defaultQualificationLevelId = NULL;

--     IF (defaultId IS NOT NULL) THEN #if default is set, then return it
--         SET defaultQualificationLevelId = defaultId;
--     ELSE #else, get 1 record and set it as default
--         IF (recordCount > 0) THEN
--             UPDATE `qualification_levels`
--             SET `default` = 1 
--             ORDER BY `id` LIMIT 1;

--             SELECT `id` INTO defaultQualificationLevelId 
--             FROM `qualification_levels`
--             WHERE `default` = 1;
--         END IF;
--     END IF;
-- END $$
-- DELIMITER ;

-- SET @a = 0;
-- call ensureDefaultQualificationLevel(@a);
#SELECT @a;


-- `qualification_titles`
DROP TABLE IF EXISTS `qualification_titles`;
CREATE TABLE IF NOT EXISTS `qualification_titles` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `qualification_level_id` int(11) NULL COMMENT 'links to qualification_levels.id',
  -- `qualification_temp_code` varchar(250) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains the titles of the qualifications';

ALTER TABLE `qualification_titles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qualification_level_id` (`qualification_level_id`);
  -- ADD KEY `qualification_temp_code` (`qualification_temp_code`);

ALTER TABLE `qualification_titles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

#re-insert the data
-- INSERT INTO `qualification_titles`
-- SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, @a, `modified_user_id`, `modified`, `created_user_id`, `created`
-- FROM `z_3699_qualification_specialisations`;

#re-insert the data from `staff_qualifications` table
-- INSERT INTO `qualification_titles` (`name`, `order`, `qualification_level_id`, `qualification_temp_code`, `created_user_id`, `created`)
-- SELECT DISTINCT CONCAT(trim(`SQ`.`qualification_title`), ' ', trim(`QS`.`name`), ' ', trim(`QL`.`name`)) AS `qualification_title_name`, 
-- 1, `SQ`.`qualification_level_id`,
-- CONCAT(trim(`SQ`.`qualification_title`), '.', trim(`SQ`.`qualification_specialisation_id`), '.', trim(`SQ`.`qualification_level_id`)) AS `qualification_title_code`,
-- 1, '1970-01-01'
-- FROM `z_3699_staff_qualifications` `SQ`
-- INNER JOIN `z_3699_qualification_specialisations` `QS` ON `QS`.`id` = `SQ`.`qualification_specialisation_id`
-- INNER JOIN `z_3699_qualification_levels` `QL` ON `QL`.id = `SQ`.`qualification_level_id`
-- ORDER BY `qualification_title_code`;

-- `staff_qualifications`

DROP TABLE IF EXISTS `staff_qualifications`;
CREATE TABLE IF NOT EXISTS `staff_qualifications` (
  `id` int(11) NOT NULL,
  `document_no` varchar(100) DEFAULT NULL,
  `graduate_year` int(4) DEFAULT NULL,
  `qualification_institution` varchar(255) NOT NULL,
  `gpa` varchar(5) DEFAULT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `qualification_title_id` int(11) NOT NULL COMMENT 'links to qualification_titles.id',
  `qualification_country_id` int(11) NOT NULL COMMENT 'links to countries.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `staff_qualifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `qualification_title_id` (`qualification_title_id`),
  ADD KEY `qualification_country_id` (`qualification_country_id`);

ALTER TABLE `staff_qualifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

#re-insert the data
-- INSERT INTO `staff_qualifications`
-- SELECT `Z`.`id`, `Z`.`document_no`, `Z`.`graduate_year`, `Q`.`name`, `Z`.`gpa`, `Z`.`file_name`, `Z`.`file_content`, 
-- `Z`.`staff_id`, `Z`.`qualification_title_id`, `Z`.`qualification_level_id`, 
-- `Z`.`modified_user_id`, `Z`.`modified`, `Z`.`created_user_id`, `Z`.`created`
-- FROM `z_3699_staff_qualifications` `Z`
-- INNER JOIN `qualification_institutions` `Q`
--     ON `Z`.`qualification_institution_id` = `Q`.`id`;

-- SELECT `SQ`.*, `QT`.`id`, `QI`.`name`
-- FROM `z_3699_staff_qualifications` `SQ`
-- INNER JOIN `qualification_titles` `QT` ON (
--     CONCAT(trim(`SQ`.`qualification_title`), '.', trim(`SQ`.`qualification_specialisation_id`), '.', trim(`SQ`.`qualification_level_id`)) = `QT`.`qualification_temp_code`
-- )
-- INNER JOIN `z_3699_qualification_institutions` `QI` ON (
--   `QI`.id = `SQ`.`qualification_institution_id`
-- )

-- `qualification_specialisation_subjects`


-- `staff_qualifications_subjects`
DROP TABLE IF EXISTS `staff_qualifications_subjects`;
CREATE TABLE IF NOT EXISTS `staff_qualifications_subjects` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_qualification_id` int(11) NOT NULL COMMENT 'links to staff_qualifications.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the subjects that can be taught by teachers with the specialisations';

ALTER TABLE `staff_qualifications_subjects`
  ADD PRIMARY KEY (`staff_qualification_id`, `education_subject_id`);


-- `labels`
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('5c3ddc98-0aec-11e7-b9c5-525400b263eb', 'Qualifications', 'file_content', 'Qualifications', 'Attachment', NULL, NULL, '1', NULL, NULL, '1', '2017-03-17 00:00:00');

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('a72ed550-1449-11e7-9f11-525400b263eb', 'Qualifications', 'education_subjects', 'Qualifications', 'Qualification Specialisation', NULL, NULL, '1', NULL, NULL, '1', '2017-03-29 00:00:00');