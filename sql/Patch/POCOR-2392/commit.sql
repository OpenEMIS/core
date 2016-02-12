-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2392', NOW());

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
