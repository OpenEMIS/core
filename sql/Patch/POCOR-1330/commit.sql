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
INSERT INTO `education_stages` (`name`, `code`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT DISTINCT `name`, `code`, 1, 1, NULL, NULL, 1, NOW()
FROM `education_grades`;

UPDATE `education_stages`
SET `order` = `id`;

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
