-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2571', NOW());

-- Backup table
CREATE TABLE `z_2571_institution_infrastructures` LIKE  `institution_infrastructures`;
INSERT INTO `z_2571_institution_infrastructures` SELECT * FROM `institution_infrastructures` WHERE 1;

-- institution_infrastructures
ALTER TABLE `institution_infrastructures` DROP `lft`;
ALTER TABLE `institution_infrastructures` DROP `rght`;

UPDATE `institution_infrastructures` SET `parent_id` = null;
-- patch Infrastructure
DROP PROCEDURE IF EXISTS patchInfrastructure;
DELIMITER $$

CREATE PROCEDURE patchInfrastructure()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE institutionId, levelId, parentId, minId INT(11);
  DECLARE infra_levels CURSOR FOR
  		SELECT `InstitutionInfrastructures`.`institution_id`, `InfrastructureLevels`.`id`, `InfrastructureLevels`.`parent_id`
  		FROM `institution_infrastructures` AS `InstitutionInfrastructures`
  		INNER JOIN `infrastructure_levels` AS `InfrastructureLevels`
  		ON `InfrastructureLevels`.`id` = `InstitutionInfrastructures`.`infrastructure_level_id`
  		AND `InfrastructureLevels`.`parent_id` <> 0
  		GROUP BY `InstitutionInfrastructures`.`institution_id`, `InfrastructureLevels`.`id`, `InfrastructureLevels`.`parent_id`
		ORDER BY `InfrastructureLevels`.`parent_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN infra_levels;

  read_loop: LOOP
    FETCH infra_levels INTO institutionId, levelId, parentId;
    IF done THEN
      LEAVE read_loop;
    END IF;

	SELECT MIN(`id`) INTO minId FROM `institution_infrastructures` WHERE `institution_id` =  institutionId AND `infrastructure_level_id` = parentId;
	UPDATE `institution_infrastructures` SET `parent_id` = minId WHERE `institution_id` =  institutionId AND `infrastructure_level_id` = levelId;

  END LOOP read_loop;

  CLOSE infra_levels;
END
$$

DELIMITER ;

CALL patchInfrastructure;

DROP PROCEDURE IF EXISTS patchInfrastructure;
