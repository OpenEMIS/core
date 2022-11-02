-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3357', NOW());

-- rename `institution_providers` to a backup table
RENAME TABLE `institution_providers` TO `z_3357_institution_providers`;

-- recreate `institution_providers` with `institution_sector_id` column
DROP TABLE IF EXISTS `institution_providers`;
CREATE TABLE IF NOT EXISTS `institution_providers` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(50) NOT NULL,
 `order` int(3) NOT NULL,
 `visible` int(1) NOT NULL DEFAULT '1',
 `editable` int(1) NOT NULL DEFAULT '1',
 `default` int(1) NOT NULL DEFAULT '0',
 `institution_sector_id` int(11) NOT NULL COMMENT 'links to institution_sectors.id',
 `international_code` varchar(50) DEFAULT NULL,
 `national_code` varchar(50) DEFAULT NULL,
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This is a field option table containing the list of user-defined providers used by institutions';

INSERT INTO `institution_providers` (`id`, `name`, `order`, `visible`, `editable`, `default`, `institution_sector_id`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `name`, `order`, `visible`, `editable`, `default`, 0, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3357_institution_providers`;

-- replace `institution_sector_id` with the sectors from `institutions` that are linked to the providers
-- if no sector links to a particular provider in `institutions`, replace it with the default or first sector
UPDATE `institution_providers`
SET `institution_sector_id` = IFNULL((
    SELECT `institutions`.`institution_sector_id`
    FROM `institutions`
    WHERE `institutions`.`institution_provider_id` = `institution_providers`.`id`
    GROUP BY `institutions`.`institution_provider_id`
), IFNULL((SELECT `id` FROM `institution_sectors` WHERE `default` = 1), (SELECT `id` FROM `institution_sectors` LIMIT 1)));

-- replace `institution_sector_id` in `institutions` with the sectors that are linked to the providers in `institution_providers`
UPDATE `institutions`
SET `institution_sector_id` = IFNULL((
    SELECT `institution_providers`.`institution_sector_id`
    FROM `institution_providers`
    WHERE `institutions`.`institution_provider_id` = `institution_providers`.`id`
    GROUP BY `institution_providers`.`id`
), IFNULL((SELECT `id` FROM `institution_sectors` WHERE `default` = 1), (SELECT `id` FROM `institution_sectors` LIMIT 1)));

-- create label for sector
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('56e0a017-7bdc-11e6-92c7-525400b263eb', 'Providers', 'institution_sector_id', 'FieldOptions -> Providers', 'Sector', 1, 1, NOW());
