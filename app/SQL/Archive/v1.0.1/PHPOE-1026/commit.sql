SET @maxOptionId := 0;
SELECT MAX(`id`) into @maxOptionId FROM `field_options`;

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'QualificationLevel', 'Levels', 'Qualification', @maxOptionId+1, 1, 1, '0000-00-00 00:00:00');

UPDATE `field_options` SET `parent` = 'Qualification', `order` = @maxOptionId+2 WHERE `code` LIKE 'QualificationSpecialisation';

RENAME TABLE `qualification_levels` TO `qualification_levels_bak` ;