DROP TABLE IF EXISTS `institutions`;

ALTER TABLE `institution_sites` DROP `institution_id`;
ALTER TABLE `institution_site_attachments` DROP `visible`;
ALTER TABLE `institution_site_attachments` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;
