-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2392', NOW());

-- Backup table
CREATE TABLE `z_2392_institution_infrastructures` LIKE  `institution_infrastructures`;
INSERT INTO `z_2392_institution_infrastructures` SELECT * FROM `institution_infrastructures` WHERE 1;

-- Start: infrastructure_ownerships
DROP TABLE IF EXISTS `infrastructure_ownerships`;
CREATE TABLE IF NOT EXISTS `infrastructure_ownerships` (
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
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- update field_options
UPDATE `field_options` SET `params` = '{"model":"FieldOption.InfrastructureOwnerships"}' WHERE `code` = 'InfrastructureOwnerships';

-- move out infrastructure_ownerships from field_option_values and start with new id
INSERT INTO `infrastructure_ownerships` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`,`created`)
SELECT `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `id`, `created` FROM `field_option_values`
WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureOwnerships');
UPDATE `field_option_values` SET `visible` = 0 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureOwnerships');

-- update new id back to field_option_values
UPDATE `field_option_values` AS `FieldOptionValues`
INNER JOIN `infrastructure_ownerships` AS `InfrastructureOwnerships` ON `InfrastructureOwnerships`.`created_user_id` = `FieldOptionValues`.`id`
SET `FieldOptionValues`.`id_new` = `InfrastructureOwnerships`.`id`;

-- pacth new id to all hasMany tables
UPDATE `institution_infrastructures` AS `InstitutionInfrastructures`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id` = `InstitutionInfrastructures`.`infrastructure_ownership_id`
SET `InstitutionInfrastructures`.`infrastructure_ownership_id` = `FieldOptionValues`.`id_new`;

-- update created_user_id in infrastructure_ownerships with the original value
UPDATE `infrastructure_ownerships` AS `InfrastructureOwnerships`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id_new` = `InfrastructureOwnerships`.`id`
SET `InfrastructureOwnerships`.`created_user_id` = `FieldOptionValues`.`created_user_id`;
-- End

-- Start: infrastructure_conditions
DROP TABLE IF EXISTS `infrastructure_conditions`;
CREATE TABLE IF NOT EXISTS `infrastructure_conditions` (
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
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- update field_options
UPDATE `field_options` SET `params` = '{"model":"FieldOption.InfrastructureConditions"}' WHERE `code` = 'InfrastructureConditions';

-- move out infrastructure_conditions from field_option_values and start with new id
INSERT INTO `infrastructure_conditions` (`name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`,`created`)
SELECT `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `id`, `created` FROM `field_option_values`
WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureConditions');
UPDATE `field_option_values` SET `visible` = 0 WHERE `field_option_id` = (SELECT `id` FROM `field_options` WHERE `code` = 'InfrastructureConditions');

-- update new id back to field_option_values
UPDATE `field_option_values` AS `FieldOptionValues`
INNER JOIN `infrastructure_conditions` AS `InfrastructureConditions` ON `InfrastructureConditions`.`created_user_id` = `FieldOptionValues`.`id`
SET `FieldOptionValues`.`id_new` = `InfrastructureConditions`.`id`;

-- pacth new id to all hasMany tables
UPDATE `institution_infrastructures` AS `InstitutionInfrastructures`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id` = `InstitutionInfrastructures`.`infrastructure_condition_id`
SET `InstitutionInfrastructures`.`infrastructure_condition_id` = `FieldOptionValues`.`id_new`;

-- update created_user_id in infrastructure_conditions with the original value
UPDATE `infrastructure_conditions` AS `InfrastructureConditions`
INNER JOIN `field_option_values` AS `FieldOptionValues` ON `FieldOptionValues`.`id_new` = `InfrastructureConditions`.`id`
SET `InfrastructureConditions`.`created_user_id` = `FieldOptionValues`.`created_user_id`;
-- End

-- institution_infrastructures
ALTER TABLE `institution_infrastructures` ADD `parent_id` INT(11) NULL DEFAULT NULL AFTER `size`;
ALTER TABLE `institution_infrastructures` ADD `lft` INT(11) NULL DEFAULT NULL AFTER `parent_id`;
ALTER TABLE `institution_infrastructures` ADD `rght` INT(11) NULL DEFAULT NULL AFTER `lft`;

-- patch Infrastructure
DROP PROCEDURE IF EXISTS patchInfrastructure;
DELIMITER $$

CREATE PROCEDURE patchInfrastructure()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE levelId, parentId, minId INT(11);
  DECLARE infra_levels CURSOR FOR
		SELECT `InfrastructureLevels`.`id`, `InfrastructureLevels`.`parent_id`
		FROM `infrastructure_levels` AS `InfrastructureLevels`
		WHERE `InfrastructureLevels`.`parent_id` <> 0
		ORDER BY `InfrastructureLevels`.`parent_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN infra_levels;

  read_loop: LOOP
    FETCH infra_levels INTO levelId, parentId;
    IF done THEN
      LEAVE read_loop;
    END IF;

	SELECT MIN(`id`) INTO minId FROM `institution_infrastructures` WHERE `infrastructure_level_id` = parentId;
	UPDATE `institution_infrastructures` SET `parent_id` = minId WHERE `infrastructure_level_id` = levelId;

  END LOOP read_loop;

  CLOSE infra_levels;
END
$$

DELIMITER ;

CALL patchInfrastructure;

DROP PROCEDURE IF EXISTS patchInfrastructure;
