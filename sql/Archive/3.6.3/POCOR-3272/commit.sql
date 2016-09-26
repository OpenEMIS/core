-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3272', NOW());

-- create backup user contacts table
CREATE TABLE `z_3272_user_contacts` (
    `id` int(11) NOT NULL,
    `value` varchar(100) NOT NULL,
     PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `z_3272_user_contacts`
SELECT `user_contacts`.`id`, `user_contacts`.`value`
FROM `user_contacts`
INNER JOIN `contact_types`
ON `contact_types`.`id` = `user_contacts`.`contact_type_id`
WHERE `contact_types`.`contact_option_id` IN (1, 2, 3);

-- remove any negative signs or hashes in numeric values
UPDATE `user_contacts`
INNER JOIN `contact_types`
ON `contact_types`.`id` = `user_contacts`.`contact_type_id`
SET `user_contacts`.`value` = REPLACE(`user_contacts`.`value`, '-', '')
WHERE `contact_types`.`contact_option_id` IN (1, 2, 3);