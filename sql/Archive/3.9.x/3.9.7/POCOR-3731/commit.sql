-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3731', NOW());

-- staff_behaviours
RENAME TABLE `staff_behaviours` TO `z_3731_staff_behaviours`;

DROP TABLE IF EXISTS `staff_behaviours`;
CREATE TABLE `staff_behaviours` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `description` text NOT NULL,
 `action` text NOT NULL,
 `date_of_behaviour` date NOT NULL,
 `time_of_behaviour` time DEFAULT NULL,
 `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
 `staff_behaviour_category_id` int(11) NOT NULL COMMENT 'links to staff_behaviour_categories.id',
 `behaviour_classification_id` int(11) NOT NULL COMMENT 'links to behaviour_classifications.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `staff_behaviour_category_id` (`staff_behaviour_category_id`),
 KEY `behaviour_classification_id` (`behaviour_classification_id`),
 KEY `staff_id` (`staff_id`),
 KEY `institution_id` (`institution_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='This table contains all behavioural records of staff';

INSERT INTO `staff_behaviours` (`id`, `description`, `action`, `date_of_behaviour`, `time_of_behaviour`, `staff_id`, `institution_id`, `staff_behaviour_category_id`, `behaviour_classification_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, CONCAT(`title`, ' - ',`description`), `action`, `date_of_behaviour`, `time_of_behaviour`, `staff_id`, `institution_id`, `staff_behaviour_category_id`, 0, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3731_staff_behaviours`;
