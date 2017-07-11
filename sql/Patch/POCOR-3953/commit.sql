-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3953', NOW());

-- guidance_types
DROP TABLE IF EXISTS `guidance_types`;
CREATE TABLE IF NOT EXISTS `guidance_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
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
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This field options table contains types of guidance';

-- institution_counselors
DROP TABLE IF EXISTS `institution_counselors`;
CREATE TABLE IF NOT EXISTS `institution_counselors` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `date` date NOT NULL,
    `description` text NOT NULL COLLATE utf8mb4_unicode_ci,
    `intervention` text NOT NULL COLLATE utf8mb4_unicode_ci,
    `file_name` varchar(250) DEFAULT NULL,
    `file_content` longblob DEFAULT NULL,
    `counselor_id` int(11) NOT NULL COMMENT 'links to security_users.id',
    `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
    `guidance_type_id` int(11) NOT NULL COMMENT 'links to guidance_types.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `counselor_id` (`counselor_id`),
    KEY `student_id` (`student_id`),
    KEY `guidance_type_id` (`guidance_type_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains counselor in the institution';

