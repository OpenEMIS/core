DELETE FROM `field_options` WHERE `code` LIKE 'QualificationLevel';

UPDATE `field_options` SET `parent` = 'Staff' WHERE `code` LIKE 'QualificationSpecialisation';

RENAME TABLE `qualification_levels_bak` TO `qualification_levels` ;