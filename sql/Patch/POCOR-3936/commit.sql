-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3936', NOW());


-- staff_behaviours
RENAME TABLE `staff_behaviours` TO `z_3936_staff_behaviours`;

DROP TABLE IF EXISTS `staff_behaviours`;
CREATE TABLE IF NOT EXISTS `staff_behaviours` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `description` text NOT NULL,
    `date_of_behaviour` date NOT NULL,
    `time_of_behaviour` time DEFAULT NULL,
    `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
    `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
    `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
    `staff_behaviour_category_id` int(11) NOT NULL COMMENT 'links to staff_behaviour_categories.id',
    `behaviour_classification_id` int(11) NOT NULL COMMENT 'links to behaviour_classifications.id',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `academic_period_id` (`academic_period_id`),
    KEY `staff_id` (`staff_id`),
    KEY `institution_id` (`institution_id`),
    KEY `staff_behaviour_category_id` (`staff_behaviour_category_id`),
    KEY `behaviour_classification_id` (`behaviour_classification_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all behavioural records of staff';

-- insert value to the staff_behaviours table from backup staff_behaviours
INSERT INTO `staff_behaviours` (`id`, `description`, `date_of_behaviour`, `time_of_behaviour`, `academic_period_id`, `staff_id`, `institution_id`, `staff_behaviour_category_id`, `behaviour_classification_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Z`.`id`, `Z`.`description`, `Z`.`date_of_behaviour`, `Z`.`time_of_behaviour`, `AP`.`id`, `Z`.`staff_id`, `Z`.`institution_id`, `Z`.`staff_behaviour_category_id`, `Z`.`behaviour_classification_id`, `Z`.`modified_user_id`, `Z`.`modified`, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3936_staff_behaviours` AS `Z`
INNER JOIN `academic_periods` AS `AP`
ON `AP`.`start_date` <= `Z`.`date_of_behaviour` AND `AP`.`end_date` >= `Z`.`date_of_behaviour`
INNER JOIN `academic_period_levels` AS `APL`
ON `APL`.`id` = `AP`.`academic_period_level_id`
AND `APL`.`level` = 1;

