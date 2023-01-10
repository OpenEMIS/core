-- PHPOE-1414
INSERT INTO `db_patches` VALUES ('PHPOE-1414');

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES
(uuid(), 'StudentFees', 'openemis_no', 'Student -> Fees', 'OpenEMIS ID', '1', '0', NOW()),
(uuid(), 'InstitutionFees', 'total', 'Institution -> Finance -> Fees', 'Total Fee', '1', '0', NOW());

UPDATE `labels` set `field`='amount_paid', `field_name`='Amount Paid' WHERE `module`='StudentFees' AND `field`='paid';
UPDATE `labels` set `field`='outstanding_fee', `field_name`='Outstanding Fee' WHERE `module`='StudentFees' AND `field`='outstanding';

ALTER TABLE `student_fees` 	CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users', 
							CHANGE `institution_site_fee_id` `institution_fee_id` INT(11) NOT NULL;

CREATE TABLE IF NOT EXISTS `z_1414_institution_site_fee_types` LIKE `institution_site_fee_types`;
INSERT INTO `z_1414_institution_site_fee_types` SELECT * FROM `institution_site_fee_types`;
ALTER TABLE `institution_site_fee_types` CHANGE `institution_site_fee_id` `institution_fee_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_fee_types` RENAME `institution_fee_types`;

CREATE TABLE IF NOT EXISTS `z_1414_institution_site_fees` LIKE `institution_site_fees`;
INSERT INTO `z_1414_institution_site_fees` SELECT * FROM `institution_site_fees`;
ALTER TABLE `institution_site_fees` CHANGE `institution_site_id` `institution_id` INT(11) NOT NULL;
ALTER TABLE `institution_site_fees` CHANGE `total` `total` DECIMAL(20,2) NULL DEFAULT NULL;
ALTER TABLE `institution_site_fees` RENAME `institution_fees`;
