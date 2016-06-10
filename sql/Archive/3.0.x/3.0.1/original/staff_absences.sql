ALTER TABLE `institution_site_staff_absences` CHANGE `first_date_absent` `start_date` DATE NULL NOT NULL ;
ALTER TABLE `institution_site_staff_absences` CHANGE `last_date_absent` `end_date` DATE NULL NOT NULL ;
ALTER TABLE `institution_site_staff_absences` CHANGE `start_time_absent` `start_time` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `institution_site_staff_absences` CHANGE `end_time_absent` `end_time` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `institution_site_staff_absences` CHANGE `full_day_absent` `full_day` INT( 1 ) NOT NULL ;
ALTER TABLE `institution_site_staff_absences` DROP `absence_type` ;