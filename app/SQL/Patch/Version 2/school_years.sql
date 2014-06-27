
 ALTER TABLE `school_years` ADD `order` INT( 11 ) NULL DEFAULT '0' AFTER `available` ;

 ALTER TABLE `school_years` ADD `visible` INT( 1 ) NULL DEFAULT '1' AFTER `order` ;
