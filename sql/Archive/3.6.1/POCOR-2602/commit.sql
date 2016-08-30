-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2602', NOW());

-- add new field option for ShiftOptions
INSERT INTO `field_options` (`id`, `plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES (NULL, 'Institution', 'ShiftOptions', 'Shift Options', 'Institution', NULL, '61', '1', NULL, NULL, '1', '2016-06-23 00:00:00');

--
-- new shift_options table
--
CREATE TABLE IF NOT EXISTS `shift_options` (
  `id` int(11) NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `shift_options`
INSERT INTO `shift_options` (`id`, `name`, `start_time`, `end_time`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'First Shift', '07:00:00', '11:00:00', 1, 1, 1, 0, NULL, NULL, NULL, NULL, 1, '2016-06-21 00:00:00'),
(2, 'Second Shift', '11:00:00', '15:00:00', 2, 1, 1, 0, NULL, NULL, NULL, NULL, 1, '2016-06-21 00:00:00'),
(3, 'Third Shift', '15:00:00', '19:00:00', 3, 1, 1, 0, NULL, NULL, NULL, NULL, 1, '2016-06-21 00:00:00'),
(4, 'Fourth Shift', '19:00:00', '23:00:00', 4, 1, 1, 0, NULL, NULL, NULL, NULL, 1, '2016-06-21 00:00:00');

-- Indexes for table `shift_options`
ALTER TABLE `shift_options`
  ADD PRIMARY KEY (`id`);

-- AUTO_INCREMENT for table `shift_options`
ALTER TABLE `shift_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;

--
-- institution_shifts
--
CREATE TABLE `z_2602_institution_shifts` LIKE `institution_shifts`; 
INSERT INTO `z_2602_institution_shifts` SELECT * FROM `institution_shifts`;

ALTER TABLE `institution_shifts` ADD `shift_option_id` INT NOT NULL AFTER `location_institution_id`;
ALTER TABLE `institution_shifts` DROP `name`;
ALTER TABLE `institution_shifts` CHANGE `location_institution_id` `location_institution_id` INT(11) NOT NULL;

--
-- patch Institution Shift
--
DROP PROCEDURE IF EXISTS patchInstitutionShift;
DELIMITER $$

CREATE PROCEDURE patchInstitutionShift()
BEGIN
  DECLARE done INT DEFAULT FALSE;
  DECLARE institutionID, academicPeriodID, shiftCounter, recCounter INT(11);
  DECLARE institution_shift_counter CURSOR FOR 
    SELECT `institution_id`, `academic_period_id`, COUNT(`id`) AS counter
    FROM `institution_shifts`
    GROUP BY `institution_id`, `academic_period_id`;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  OPEN institution_shift_counter;

  read_loop: LOOP
    FETCH institution_shift_counter INTO institutionID, academicPeriodID, shiftCounter;
    IF done THEN
      LEAVE read_loop;
    END IF;

    SET recCounter = 0;
    WHILE recCounter < shiftCounter DO
      UPDATE `institution_shifts`
      SET `shift_option_id` = recCounter+1
      WHERE `id` IN (
        SELECT `id` FROM (
            SELECT `id` 
            FROM `institution_shifts`
            WHERE `institution_id` = institutionID
            AND `academic_period_id` = academicPeriodID
            ORDER BY `start_time` ASC  
            LIMIT recCounter, 1) tempTable);
      SET recCounter = recCounter + 1;
    END WHILE;

  END LOOP read_loop;

  CLOSE institution_shift_counter;
END

$$

DELIMITER ;

CALL patchInstitutionShift;

DROP PROCEDURE IF EXISTS patchInstitutionShift;

--
-- Label
--
UPDATE `labels` 
SET `field` = 'shift_option_id', `field_name` = 'Shift' 
WHERE `module` = 'InstitutionShifts'
AND `field` = 'name'
AND `module_name` = 'Institutions -> Shifts'
AND `field_name` = 'Shift Name';

UPDATE `labels` 
SET `field_name` = 'Owner',
`field` = 'institution_id' 
WHERE `module` = 'InstitutionShifts'
AND `field` = 'location'
AND `module_name` = 'Institutions -> Shifts';

UPDATE `labels` 
SET `field_name` = 'Occupier' 
WHERE `module` = 'InstitutionShifts'
AND `field` = 'location_institution_id'
AND `module_name` = 'Institutions -> Shifts';

UPDATE `labels` 
SET `module_name` = 'Institutions -> Shifts'
WHERE `module` = 'InstitutionShifts';

UPDATE `labels`
SET  `field` = 'academic_period_id'
WHERE `module` = 'InstitutionShifts'
AND `field` = 'Academic_period_id'
AND `module_name` = 'Institutions -> Shifts';

--
-- institutions table
--
CREATE TABLE `z_2602_institutions` LIKE `institutions`; 
INSERT INTO `z_2602_institutions` SELECT * FROM `institutions`;

ALTER TABLE `institutions` ADD `shift_type` INT NOT NULL COMMENT '1=Single Shift Owner, 2=Single Shift Occupier, 3=Multiple Shift Owner, 4=Multiple Shift Occupier' AFTER `latitude`;

--
-- patch patchInstitutionShiftType
--
-- owner
UPDATE `institutions` I
INNER JOIN (
      SELECT `institution_id`, COUNT(`id`) AS counter 
      FROM `institution_shifts` 
      WHERE `academic_period_id` = (SELECT `id` FROM `academic_periods` WHERE `current` = 1 ) 
      GROUP BY `institution_id`
    )S ON S.`institution_id` = I.`id`
SET `shift_type` = IF(S.`counter` > 1, 3, 1);

-- occupier
UPDATE `institutions` I
INNER JOIN (
      SELECT `location_institution_id`, COUNT(`id`) AS counter
        FROM `institution_shifts`
        WHERE `academic_period_id` = (SELECT `id` FROM `academic_periods` WHERE `current` = 1 ) 
        AND `location_institution_id` <> `institution_id`
        GROUP BY `location_institution_id`
    )S ON S.`location_institution_id` = I.`id`
SET `shift_type` = IF(S.`counter` > 1, 4, 2);