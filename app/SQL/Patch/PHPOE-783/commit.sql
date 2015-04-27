-- 
-- PHPOE-783 commit.sql
-- 

INSERT INTO `contact_options` 
(`id`, `name`, `order`, `visible`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT NULL, 'Emergency', `contact_options`.`order` as `order` , '1', NULL, NULL, NULL, NULL, '1', NOW()
FROM `contact_options`
WHERE `contact_options`.`name` = 'Other';

UPDATE `contact_options` SET `contact_options`.`order`=`contact_options`.`order`+1 WHERE `contact_options`.`name` = 'Other';

INSERT INTO `contact_types` 
(`id`, `contact_option_id`, `name`, `order`, `visible`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT 
	NULL, 
	`contact_options`.`id` as `contact_option_id`, 
	'Mother', 
	1, 
	1, 
	NULL, 
	NULL, 
	NULL,
	NULL, 
	1, 
	NOW()
FROM `contact_options`
WHERE `contact_options`.`name` = 'Emergency';

INSERT INTO `contact_types` 
(`id`, `contact_option_id`, `name`, `order`, `visible`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT 
	NULL, 
	`contact_options`.`id` as `contact_option_id`, 
	'Father', 
	2, 
	1, 
	NULL, 
	NULL, 
	NULL,
	NULL, 
	1, 
	NOW()
FROM `contact_options`
WHERE `contact_options`.`name` = 'Emergency';
