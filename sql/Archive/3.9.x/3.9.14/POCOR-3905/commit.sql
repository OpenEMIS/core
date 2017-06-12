-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3905', NOW());

-- education_grades
RENAME TABLE `education_grades` TO `z_3905_education_grades`;

DROP TABLE IF EXISTS `education_grades`;
CREATE TABLE IF NOT EXISTS `education_grades` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(50) NOT NULL,
    `name` varchar(150) NOT NULL,
    `admission_age` int(3) NOT NULL,
    `order` int(3) NOT NULL,
    `visible` int(1) NOT NULL DEFAULT '1',
    `education_programme_id` int(11) NOT NULL COMMENT 'links to education_programmes.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `education_programme_id` (`education_programme_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of education grades linked to specific education programmes';

-- insert data to the new table
INSERT INTO `education_grades` (`id`, `code`, `name`, `admission_age`, `order`, `visible`, `education_programme_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `grades`.`id`, `grades`.`code`, `grades`.`name`, `cycles`.`admission_age`, `grades`.`order`, `grades`.`visible`, `grades`.`education_programme_id`, `grades`.`modified_user_id`, `grades`.`modified`, `grades`.`created_user_id`, `grades`.`created`
FROM `z_3905_education_grades` AS `grades`
LEFT JOIN `education_programmes` AS `programmes`
    ON `programmes`.`id` = `grades`.`education_programme_id`
LEFT JOIN `education_cycles` AS `cycles`
    ON `cycles`.`id` = `programmes`.`education_cycle_id`;

-- to update the order and admission age
SET @order:=0;
SET @pid:=0;

UPDATE education_grades g,
(SELECT @order:= IF(@pid = `education_programme_id`, @order:=@order+1, @order:=1) AS NEWORDER,
        @pid:= `education_programme_id`,
        `id`
        FROM education_grades
        ORDER BY `education_programme_id`
) AS s
set g.`order` = s.`NEWORDER`,
    g.`admission_age` = g.`admission_age` + s.`NEWORDER` - 1
where g.`id` = s.`id`;
