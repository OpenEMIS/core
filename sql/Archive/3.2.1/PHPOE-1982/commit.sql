-- db_patches
CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `db_patches` VALUES ('PHPOE-1982');

-- institution_site_assessments
ALTER TABLE `institution_site_assessments` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '-1 -> Expired, 0 -> New, 1 -> Draft, 2 -> Completed';

-- security_functions
UPDATE `security_functions` SET `_view` = 'Assessments.index|Results.index', `_edit` = 'Results.indexEdit', `_add` = NULL, `_delete` = 'Assessments.remove', `_execute` = NULL WHERE `id` = 1015;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) VALUES (uuid(), 'Results', 'openemis_no', 'Institutions -> Students -> Results', 'OpenEMIS ID', 1, NOW());
