-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3983', NOW());


-- institution_statuses
RENAME TABLE `institution_statuses` TO `z_3983_institution_statuses`;

DROP TABLE IF EXISTS `institution_statuses`;
CREATE TABLE IF NOT EXISTS `institution_statuses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(100) NOT NULL,
    `name` varchar(250) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This is a field option table containing the list of user-defined statuses used by institutions';

-- insert value to institution_statuses
INSERT INTO `institution_statuses` (`id`, `code`, `name`)
VALUES  (1, 'ACTIVE', 'Active'),
        (2, 'INACTIVE', 'Inactive');

-- institutions
RENAME TABLE `institutions` TO `z_3983_institutions`;
CREATE TABLE `institutions` LIKE `z_3983_institutions`;

INSERT INTO `institutions` (`id`, `name`, `alternative_name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `date_opened`, `year_opened`, `date_closed`, `year_closed`, `longitude`, `latitude`, `shift_type`, `classification`, `area_id`, `area_administrative_id`, `institution_locality_id`, `institution_type_id`, `institution_ownership_id`, `institution_status_id`, `institution_sector_id`, `institution_provider_id`, `institution_gender_id`, `institution_network_connectivity_id`, `security_group_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `alternative_name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `date_opened`, `year_opened`, `date_closed`, `year_closed`, `longitude`, `latitude`, `shift_type`, `classification`, `area_id`, `area_administrative_id`, `institution_locality_id`, `institution_type_id`, `institution_ownership_id`, (IF(`date_closed` IS NULL OR `date_closed` >= CURDATE(), 1, 2)), `institution_sector_id`, `institution_provider_id`, `institution_gender_id`, `institution_network_connectivity_id`, `security_group_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3983_institutions`;

-- import_mapping
CREATE TABLE `z_3983_import_mapping` LIKE `import_mapping`;
INSERT `z_3983_import_mapping` SELECT * FROM `import_mapping`;

DELETE FROM `import_mapping` WHERE `model` = 'Institution.Institutions' AND `column_name` = 'institution_status_id';
UPDATE `import_mapping` SET `id` = `id` - 1, `order` = `order` - 1 WHERE `model` = 'Institution.Institutions' AND `id` > 20;

