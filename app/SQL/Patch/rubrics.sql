RENAME TABLE `institution_site_class_teachers` TO `institution_site_class_staffs` ;

ALTER TABLE `institution_site_class_staffs` CHANGE `teacher_id` `staff_id` INT( 11 ) NOT NULL ;

ALTER TABLE `institution_site_class_staffs ` DROP INDEX `teacher_id`, ADD INDEX `staff_id` ( `staff_id` ) COMMENT ‘’;

ALTER TABLE `quality_institution_rubrics` CHANGE `teacher_id` `staff_id` INT( 11 ) NOT NULL ;

ALTER TABLE `quality_institution_rubrics ` DROP INDEX `teacher_id`, ADD INDEX `staff_id` ( `staff_id` ) COMMENT ‘’;

ALTER TABLE `quality_institution_visits` CHANGE `teacher_id` `staff_id` INT( 11 ) NOT NULL ;

ALTER TABLE `quality_institution_visits` DROP INDEX `teacher_id`, ADD INDEX `staff_id` ( `staff_id` ) COMMENT ‘’;

ALTER TABLE `quality_institution_visit_attachments` ADD `visible` INT( 1 ) NOT NULL DEFAULT '1' AFTER `file_content` ;

