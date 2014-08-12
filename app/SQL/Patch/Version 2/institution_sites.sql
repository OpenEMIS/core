ALTER TABLE `institution_sites` ADD `institution_site_provider_id` INT NOT NULL AFTER `institution_site_status_id` ,
ADD INDEX ( `institution_site_provider_id` ) ;

ALTER TABLE `institution_sites` ADD `institution_site_sector_id` INT NOT NULL AFTER `institution_site_status_id` ,
ADD INDEX ( `institution_site_sector_id` ) ;

ALTER TABLE `institution_sites` ADD `institution_site_gender_id` INT( 5 ) NOT NULL AFTER `institution_site_provider_id` ,
ADD INDEX ( `institution_site_gender_id` ) ;
