-- `system_patches`
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3699', NOW());


-- `qualification_specialisations`
RENAME TABLE `qualification_specialisations` TO `z_3699_qualification_specialisations`;


-- `qualification_levels`
#ensure that there is at least one default qualification_levels
DROP PROCEDURE IF EXISTS `ensureDefaultQualificationLevel`;

DELIMITER $$
CREATE PROCEDURE `ensureDefaultQualificationLevel` (OUT defaultQualificationLevelId int)
BEGIN
    DECLARE recordCount INT;
    DECLARE defaultId INT;

    SELECT COUNT(*) INTO recordCount 
    FROM `qualification_levels`;

    SELECT `id` INTO defaultId 
    FROM `qualification_levels`
    WHERE `default` = 1;

    SET defaultQualificationLevelId = NULL;

    IF (defaultId IS NOT NULL) THEN #if default is set, then return it
        SET defaultQualificationLevelId = defaultId;
    ELSE #else, get 1 record and set it as default
        IF (recordCount > 0) THEN
            UPDATE `qualification_levels`
            SET `default` = 1 
            ORDER BY `id` LIMIT 1;

            SELECT `id` INTO defaultQualificationLevelId 
            FROM `qualification_levels`
            WHERE `default` = 1;
        END IF;
    END IF;
END $$
DELIMITER ;

SET @a = 0;
call ensureDefaultQualificationLevel(@a);
#SELECT @a;


-- `qualification_titles`
DROP TABLE IF EXISTS `qualification_titles`;
CREATE TABLE IF NOT EXISTS `qualification_titles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains the titles of the qualifications';

ALTER TABLE `qualification_titles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qualification_level_id` (`qualification_level_id`);

ALTER TABLE `qualification_titles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

#re-insert the data
INSERT INTO `qualification_titles`
SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, @a, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3699_qualification_specialisations`;


-- `staff_qualifications`
RENAME TABLE `staff_qualifications` TO `z_3699_staff_qualifications`;

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

-- `qualification_specialisation_subjects`
RENAME TABLE `qualification_specialisation_subjects` TO `z_3699_qualification_specialisation_subjects`;


-- `staff_qualifications_subjects`
DROP TABLE IF EXISTS `staff_qualifications_subjects`;
CREATE TABLE IF NOT EXISTS `staff_qualifications_subjects` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_qualification_id` int(11) NOT NULL COMMENT 'links to staff_qualifications.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the subjects that can be taught by teachers with the specialisations';

ALTER TABLE `staff_qualifications_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_qualification_id` (`staff_qualification_id`),
  ADD KEY `education_subject_id` (`education_subject_id`);


--`labels`
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('5c3ddc98-0aec-11e7-b9c5-525400b263eb', 'Qualifications', 'file_content', 'Staff > Qualifications', 'Attachment', NULL, NULL, '1', NULL, NULL, '1', '2017-03-17 00:00:00');
