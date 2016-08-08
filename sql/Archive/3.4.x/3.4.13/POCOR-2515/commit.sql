INSERT INTO `db_patches` VALUES ('POCOR-2515', NOW());

CREATE TABLE z2515_institution_shifts LIKE institution_shifts;
INSERT INTO z2515_institution_shifts SELECT * FROM institution_shifts;

UPDATE institution_shifts SET start_time = STR_TO_DATE(start_time, '%h:%i %p');
UPDATE institution_shifts SET end_time = STR_TO_DATE(end_time, '%h:%i %p');

ALTER TABLE `institution_shifts` CHANGE `start_time` `start_time` TIME NOT NULL, CHANGE `end_time` `end_time` TIME NOT NULL;

UPDATE `labels` SET `field_name` = 'Location' WHERE field = 'location_institution_id' AND module_name = 'Institutions -> Shifts';
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (uuid(), 'InstitutionShifts', 'location', 'Institutions -> Shifts', 'Occupied By', NULL, NULL, '1', NULL, NULL, '1', now());