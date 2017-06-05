-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-1330', NOW());

-- education_absolute_grades
DROP TABLE IF EXISTS `education_absolute_grades`;
CREATE TABLE IF NOT EXISTS `education_absolute_grades` (
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

-- insert value to education_absolute_grades
INSERT INTO `education_absolute_grades` (`id`, `name`, `code`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT DISTINCT NULL, `name`, `code`, 1, 1, NULL, NULL, 1, NOW()
FROM `education_grades`;

-- update order to be the same as id
UPDATE `education_absolute_grades`
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
    `education_absolute_grade_id` int(11) NOT NULL COMMENT 'links to education_absolute_grades.id',
    `education_programme_id` int(11) NOT NULL COMMENT 'links to education_programmes.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `education_absolute_grade_id` (`education_absolute_grade_id`),
    KEY `education_programme_id` (`education_programme_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of education grades linked to specific education programmes';

INSERT INTO `education_grades` (`id`, `code`, `name`, `admission_age`, `order`, `visible`, `education_absolute_grade_id`, `education_programme_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Z`.`id`, `Z`.`code`, `Z`.`name`, `Z`.`admission_age`, `Z`.`order`, `Z`.`visible`, `EAG`.`id`, `Z`.`education_programme_id`, `Z`.`modified_user_id`, `Z`.`modified`, `Z`.`created_user_id`, `Z`.`created`
FROM `z_1330_education_grades` AS `Z`
INNER JOIN `education_absolute_grades` AS `EAG`
ON `EAG`.`code` = `Z`.`code` AND `EAG`.`name` = `Z`.`name`;

-- security_functions
CREATE TABLE `z_1330_security_functions` LIKE `security_functions`;
INSERT `z_1330_security_functions` SELECT * FROM `security_functions`;

UPDATE `security_functions`
SET `_view` = 'AbsoluteGrades.index|AbsoluteGrades.view|Subjects.index|Subjects.view|Certifications.index|Certifications.view|FieldOfStudies.index|FieldOfStudies.view|ProgrammeOrientations.index|ProgrammeOrientations.view',
    `_edit` = 'AbsoluteGrades.edit|Subjects.edit|Certifications.edit|FieldOfStudies.edit|ProgrammeOrientations.edit',
    `_add` = 'AbsoluteGrades.add|Subjects.add|Certifications.add|FieldOfStudies.add|ProgrammeOrientations.add',
    `_delete` = 'AbsoluteGrades.remove|Subjects.remove|Certifications.remove|FieldOfStudies.remove|ProgrammeOrientations.remove'
WHERE `security_functions`.`id` = 5009 ;












