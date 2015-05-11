--
-- PHPOE-1387 commit.sql
-- 

ALTER TABLE `institution_sites` ADD `security_group_id` INT(11) NOT NULL DEFAULT '0' AFTER `institution_site_gender_id`,
ADD INDEX `security_group_id` (`security_group_id`) ;

ALTER TABLE `security_group_institution_sites` ADD `id` CHAR(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL FIRST;

SET @id := 0 ;
UPDATE `security_group_institution_sites`
SET `id` = (@id := @id + 1);

ALTER TABLE `security_group_institution_sites` DROP PRIMARY KEY, ADD PRIMARY KEY (`id`);


INSERT INTO `security_groups` 
(`name`, `modified_user_id`, `created_user_id`, `created`)
SELECT 	`is`.`name` AS `name`,
		`is`.`id` AS `modified_user_id`,
		'1',
		'0000-00-00 00:00:00'
FROM `institution_sites` AS `is`;

UPDATE `institution_sites` AS `is`
INNER JOIN `security_groups` AS `sg` ON (`is`.`name` = `sg`.`name` AND `is`.`id` = `sg`.`modified_user_id`)
SET `is`.`security_group_id` = `sg`.`id`;

UPDATE `security_groups`
SET `modified_user_id` = NULL, `created` = NOW()
WHERE `created` = '0000-00-00 00:00:00';



INSERT INTO `security_group_institution_sites`
(`id`, `security_group_id`, `institution_site_id`, `created_user_id`, `created`)
SELECT CONCAT_WS('-', `is`.`id`, `is`.`security_group_id`) as `id`
	 , `is`.`security_group_id` AS `security_group_id`
     , `is`.`id` AS `institution_site_id`
     , `is`.`created_user_id` AS `created_user_id`
     , `is`.`created` AS `created`
  FROM `institution_sites` AS `is`;

