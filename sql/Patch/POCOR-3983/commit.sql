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

DROP TABLE IF EXISTS `institutions`;
CREATE TABLE IF NOT EXISTS `institutions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(150) NOT NULL,
    `alternative_name` varchar(150) DEFAULT NULL,
    `code` varchar(20) NOT NULL,
    `address` text NOT NULL,
    `postal_code` varchar(20) DEFAULT NULL,
    `contact_person` varchar(100) DEFAULT NULL,
    `telephone` varchar(30) DEFAULT NULL,
    `fax` varchar(30) DEFAULT NULL,
    `email` varchar(100) DEFAULT NULL,
    `website` varchar(100) DEFAULT NULL,
    `date_opened` date NOT NULL,
    `year_opened` int(4) NOT NULL,
    `date_closed` date DEFAULT NULL,
    `year_closed` int(4) DEFAULT NULL,
    `longitude` varchar(15) DEFAULT NULL,
    `latitude` varchar(15) DEFAULT NULL,
    `shift_type` int(11) NOT NULL COMMENT '1=Single Shift Owner, 2=Single Shift Occupier, 3=Multiple Shift Owner, 4=Multiple Shift Occupier',
    `classification` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> Academic Institution, 2 -> Non-academic institution',
    `area_id` int(11) NOT NULL COMMENT 'links to areas.id',
    `area_administrative_id` int(11) DEFAULT NULL,
    `institution_locality_id` int(11) NOT NULL COMMENT 'links to institution_localities.id',
    `institution_type_id` int(11) NOT NULL COMMENT 'links to institution_types.id',
    `institution_ownership_id` int(11) NOT NULL COMMENT 'links to institution_ownerships.id',
    `institution_status_id` int(11) NOT NULL COMMENT 'links to institution_statuses.id',
    `institution_sector_id` int(11) NOT NULL COMMENT 'links to institution_sectors.id',
    `institution_provider_id` int(11) NOT NULL COMMENT 'links to institution_providers.id',
    `institution_gender_id` int(5) NOT NULL COMMENT 'links to institution_genders.id',
    `institution_network_connectivity_id` int(11) NOT NULL COMMENT 'links to institution_network_connectivities.id',
    `security_group_id` int(11) NOT NULL DEFAULT '0',
    `modified_user_id` int(11) DEFAULT NULL,
    `modified` datetime DEFAULT NULL,
    `created_user_id` int(11) NOT NULL,
    `created` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `area_id` (`area_id`),
    KEY `security_group_id` (`security_group_id`),
    KEY `institution_locality_id` (`institution_locality_id`),
    KEY `institution_type_id` (`institution_type_id`),
    KEY `institution_ownership_id` (`institution_ownership_id`),
    KEY `institution_status_id` (`institution_status_id`),
    KEY `institution_sector_id` (`institution_sector_id`),
    KEY `institution_provider_id` (`institution_provider_id`),
    KEY `institution_gender_id` (`institution_gender_id`),
    KEY `institution_network_connectivity_id` (`institution_network_connectivity_id`),
    KEY `area_administrative_id` (`area_administrative_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains information of every institution';

INSERT INTO `institutions` (`id`, `name`, `alternative_name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `date_opened`, `year_opened`, `date_closed`, `year_closed`, `longitude`, `latitude`, `shift_type`, `classification`, `area_id`, `area_administrative_id`, `institution_locality_id`, `institution_type_id`, `institution_ownership_id`, `institution_status_id`, `institution_sector_id`, `institution_provider_id`, `institution_gender_id`, `institution_network_connectivity_id`, `security_group_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `alternative_name`, `code`, `address`, `postal_code`, `contact_person`, `telephone`, `fax`, `email`, `website`, `date_opened`, `year_opened`, `date_closed`, `year_closed`, `longitude`, `latitude`, `shift_type`, `classification`, `area_id`, `area_administrative_id`, `institution_locality_id`, `institution_type_id`, `institution_ownership_id`, (IF(`date_closed` IS NULL OR `date_closed` >= CURDATE(), 1, 2)), `institution_sector_id`, `institution_provider_id`, `institution_gender_id`, `institution_network_connectivity_id`, `security_group_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3983_institutions`;

-- import_mapping
DELETE FROM `import_mapping` WHERE `model` = 'Institution.Institutions' AND `column_name` = 'institution_status_id';
UPDATE `import_mapping` SET `id` = `id`-1, `order` = `order`-1 WHERE `model` = 'Institution.Institutions' AND `id` > 20;

