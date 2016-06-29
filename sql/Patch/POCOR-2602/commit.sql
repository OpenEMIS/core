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
-- alter institution_shifts
--
ALTER TABLE `institution_shifts` ADD `shift_option_id` INT NOT NULL AFTER `location_institution_id`;
ALTER TABLE `institution_shifts` DROP `name`;

-- patch old data
UPDATE `institution_shifts`
SET `shift_option_id` = 1
WHERE `shift_option_id` = 0;

--
-- Label
--
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('c934bb35-38ec-11e6-bc26-525400b263eb', 'InstitutionShifts', 'shift_option_id', 'Institution -> Shifts', 'Shift', NULL, NULL, '1', NULL, NULL, '1', '2016-06-23 00:00:00');

UPDATE `labels` 
SET `field_name` = 'Owner' 
WHERE `module` = 'InstitutionShifts'
AND `field` = 'location'
AND `module_name` = 'Institutions -> Shifts';

UPDATE `labels` 
SET `field_name` = 'Occupier' 
WHERE `module` = 'InstitutionShifts'
AND `field` = 'location_institution_id'
AND `module_name` = 'Institutions -> Shifts';

--
-- institutions table
---
ALTER TABLE `institutions` ADD `shift_type` INT NULL COMMENT '1=Single Shift Owner, 2=Single Shift Occupier, 3=Multiple Shift Owner, 4=Multiple Shift Occupier' AFTER `latitude`;
