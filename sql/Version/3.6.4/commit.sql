-- POCOR-3332
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3332', NOW());

-- institution_infrastructures
CREATE TABLE IF NOT EXISTS `z_3332_institution_infrastructures` (
    `id` int(11) NOT NULL,
    `code` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains the infrastructure information of institutions';

ALTER TABLE `z_3332_institution_infrastructures`
    ADD PRIMARY KEY (`id`),
    ADD KEY `code` (`code`);

INSERT INTO `z_3332_institution_infrastructures`
SELECT `id`, `code` FROM `institution_infrastructures`;

-- cleanup parent_id 0 institution_infrastructures
DELETE FROM `institution_infrastructures`
WHERE `parent_id` = 0;

-- patch institution_infrastructures

-- patch `institution_infrastructures` level 1
DROP PROCEDURE IF EXISTS patchJordanInfraLevel1;
DELIMITER $$

CREATE PROCEDURE patchJordanInfraLevel1()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE institutionID, infrastructureCounter, recCounter INT(11);
  DECLARE institutionCode, tempCounter VARCHAR(100);
  DECLARE infrastructures_cursor CURSOR FOR
    SELECT I2.`id`, I2.`code`, COUNT(I1.`id`) AS counter
    FROM `institution_infrastructures` I1
    INNER JOIN `institutions` I2 ON I2.`id` = I1.`institution_id`
    WHERE I1.`infrastructure_level_id` = 1
    #AND I2.`id` IN (440)
    GROUP BY I2.`id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN infrastructures_cursor;

  read_loop: LOOP
    FETCH infrastructures_cursor INTO institutionID, institutionCode, infrastructureCounter;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SET recCounter = 0;
    WHILE recCounter < infrastructureCounter DO

      SET tempCounter = recCounter + 1;
      IF tempCounter < 10 THEN
        SET tempCounter = CONCAT('0', tempCounter);
      END IF;

      UPDATE `institution_infrastructures`
      SET `code` = CONCAT(institutionCode, '-', tempCounter)
      WHERE `id` IN (
        SELECT `id` FROM (
            SELECT `id`
            FROM `institution_infrastructures`
            WHERE `institution_id` = institutionID
            AND `infrastructure_level_id` = 1
            ORDER BY `id`
            LIMIT recCounter, 1) tempTable)
      AND `infrastructure_level_id` = 1
      AND `institution_id` = institutionID;
      SET recCounter = recCounter + 1;
    END WHILE;

  END LOOP read_loop;

  CLOSE infrastructures_cursor;
END

$$

DELIMITER ;

CALL patchJordanInfraLevel1;

DROP PROCEDURE IF EXISTS patchJordanInfraLevel1;


-- patch `institution_infrastructures` level 2
DROP PROCEDURE IF EXISTS patchJordanInfraLevel2;
DELIMITER $$

CREATE PROCEDURE patchJordanInfraLevel2()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE infraParentID, parentCounter, recCounter INT(11);
  DECLARE infraParentCode, tempCounter VARCHAR(100);
  DECLARE infrastructures_cursor CURSOR FOR
    SELECT I2.`parent_id`, I1.`code`, I2.`counter`
    FROM `institution_infrastructures` I1
    INNER JOIN (
        SELECT `parent_id`, COUNT(`id`) AS `counter`
        FROM `institution_infrastructures`
        WHERE `infrastructure_level_id` = 2
        AND `parent_id` <> 0
        GROUP BY `parent_id`
    )I2 ON I2.`parent_id` = I1.`id`;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN infrastructures_cursor;

  read_loop: LOOP
    FETCH infrastructures_cursor INTO infraParentID, infraParentCode, parentCounter;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SET recCounter = 0;
    WHILE recCounter < parentCounter DO

      SET tempCounter = recCounter + 1;
      IF tempCounter < 10 THEN
        SET tempCounter = CONCAT('0', tempCounter);
      END IF;

      UPDATE `institution_infrastructures`
      SET `code` = CONCAT(infraParentCode, tempCounter)
      WHERE `id` IN (
        SELECT `id` FROM (
            SELECT `id`
            FROM `institution_infrastructures`
            WHERE `parent_id` = infraParentID
            AND `infrastructure_level_id` = 2
            ORDER BY `id`
            LIMIT recCounter, 1) tempTable)
      AND `infrastructure_level_id` = 2;
      SET recCounter = recCounter + 1;
    END WHILE;

  END LOOP read_loop;

  CLOSE infrastructures_cursor;
END

$$

DELIMITER ;

CALL patchJordanInfraLevel2;

DROP PROCEDURE IF EXISTS patchJordanInfraLevel2;

-- patch `institution_infrastructures` level 3
DROP PROCEDURE IF EXISTS patchJordanInfraLevel3;
DELIMITER $$

CREATE PROCEDURE patchJordanInfraLevel3()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE infraParentID, parentCounter, recCounter INT(11);
  DECLARE infraParentCode, tempCounter VARCHAR(100);
  DECLARE infrastructures_cursor CURSOR FOR
    SELECT I2.`parent_id`, I1.`code`, I2.`counter`
    FROM `institution_infrastructures` I1
    INNER JOIN (
        SELECT `parent_id`, COUNT(`id`) AS `counter`
        FROM `institution_infrastructures`
        WHERE `infrastructure_level_id` = 3
        AND `parent_id` <> 0
        GROUP BY `parent_id`
    )I2 ON I2.`parent_id` = I1.`id`;

  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN infrastructures_cursor;

  read_loop: LOOP
    FETCH infrastructures_cursor INTO infraParentID, infraParentCode, parentCounter;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SET recCounter = 0;
    WHILE recCounter < parentCounter DO

      SET tempCounter = recCounter + 1;
      IF tempCounter < 10 THEN
        SET tempCounter = CONCAT('0', tempCounter);
      END IF;

      UPDATE `institution_infrastructures`
      SET `code` = CONCAT(infraParentCode, tempCounter)
      WHERE `id` IN (
        SELECT `id` FROM (
            SELECT `id`
            FROM `institution_infrastructures`
            WHERE `parent_id` = infraParentID
            AND `infrastructure_level_id` = 3
            ORDER BY `id`
            LIMIT recCounter, 1) tempTable)
      AND `infrastructure_level_id` = 3;
      SET recCounter = recCounter + 1;
    END WHILE;

  END LOOP read_loop;

  CLOSE infrastructures_cursor;
END

$$

DELIMITER ;

CALL patchJordanInfraLevel3;

DROP PROCEDURE IF EXISTS patchJordanInfraLevel3;

-- institution_rooms
CREATE TABLE IF NOT EXISTS `z_3332_institution_rooms` (
  `id` int(11) NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for table `institution_rooms`
ALTER TABLE `z_3332_institution_rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `code` (`code`);

INSERT INTO `z_3332_institution_rooms`
SELECT `id`, `code` FROM `institution_rooms`;

-- cleanup institution_infrastructures 0 institution_rooms
DELETE FROM `institution_rooms`
WHERE `institution_infrastructure_id` = 0;

-- patch `institutions_rooms` (level 4 & 5)
DROP PROCEDURE IF EXISTS patchJordanInfraLevel45;
DELIMITER $$

CREATE PROCEDURE patchJordanInfraLevel45(IN academicPeriodID INT(11))
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE infraParentID, parentCounter, recCounter INT(11);
  DECLARE infraParentCode, tempCounter, newCode VARCHAR(100);

  DECLARE infrastructures_cursor CURSOR FOR
    SELECT R.`institution_infrastructure_id`, I.`code`, COUNT(R.`id`) AS counter
    FROM `institution_rooms` R
    INNER JOIN `institution_infrastructures` I ON I.`id` = R.`institution_infrastructure_id`
    WHERE R.`academic_period_id` = academicPeriodID
    #AND I.`institution_id` IN (1362)
    GROUP BY R.`institution_infrastructure_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN infrastructures_cursor;

  read_loop: LOOP
    FETCH infrastructures_cursor INTO infraParentID, infraParentCode, parentCounter;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SET recCounter = 0;
    WHILE recCounter < parentCounter DO

      SET tempCounter = recCounter + 1;
      IF tempCounter < 10 THEN
        SET tempCounter = CONCAT('0', tempCounter);
      END IF;

      SET newCode = CONCAT(infraParentCode, tempCounter);

      UPDATE `institution_rooms`
      SET `name` = IF(`name` = `code`, newCode, `name`),
          `code` = newCode
      WHERE `id` IN (
        SELECT `id` FROM (
            SELECT `id`
            FROM `institution_rooms`
            WHERE `institution_infrastructure_id` = infraParentID
            AND `academic_period_id` = academicPeriodID
            ORDER BY `room_type_id`, `id`
            LIMIT recCounter, 1) tempTable)
      AND `academic_period_id` = academicPeriodID;
      SET recCounter = recCounter + 1;
    END WHILE;

  END LOOP read_loop;

  CLOSE infrastructures_cursor;
END

$$

DELIMITER ;

SET @academicPeriodID := 0;

SELECT `id` INTO @academicPeriodID FROM `academic_periods`
WHERE `current` = 1;

CALL patchJordanInfraLevel45(@academicPeriodID);

DROP PROCEDURE IF EXISTS patchJordanInfraLevel45;

-- update record which is not current academic period.
UPDATE `institution_rooms` R1
INNER JOIN `institution_rooms` R2
    ON R2.`previous_room_id` = R1.`id`
    AND R2.`academic_period_id` = @academicPeriodID
SET R1.`code` = R2.`code`
WHERE R1.`academic_period_id` <> @academicPeriodID;


-- POCOR-3357
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
SET `institution_sector_id` = (
    SELECT `institution_providers`.`institution_sector_id`
    FROM `institution_providers`
    WHERE `institutions`.`institution_provider_id` = `institution_providers`.`id`
    GROUP BY `institution_providers`.`id`
);

-- create label for sector
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('56e0a017-7bdc-11e6-92c7-525400b263eb', 'Providers', 'institution_sector_id', 'FieldOptions -> Providers', 'Sector', 1, 1, NOW());


-- POCOR-3347
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3347', NOW());

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT NULL, NULL, 'There are no shifts configured for the selected academic period', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual
WHERE NOT EXISTS (SELECT * FROM `translations` WHERE `en` = 'There are no shifts configured for the selected academic period');

-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3215', NOW());

-- import_mapping
UPDATE `import_mapping` SET `description` = 'Education Code' WHERE `import_mapping`.`id` = 15;

-- 3.6.4
UPDATE config_items SET value = '3.6.4' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
