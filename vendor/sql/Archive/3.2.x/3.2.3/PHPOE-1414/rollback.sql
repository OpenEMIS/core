-- PHPOE-1414
DELETE FROM `labels` WHERE `labels`.`module`='StudentFees' AND `labels`.`field`='openemis_no';
DELETE FROM `labels` WHERE `labels`.`module`='InstitutionFees' AND `labels`.`field`='total';

UPDATE `labels` set `field`='paid', `field_name`='Paid' WHERE `module`='StudentFees' AND `field`='amount_paid';
UPDATE `labels` set `field`='outstanding', `field_name`='Outstanding' WHERE `module`='StudentFees' AND `field`='outstanding_fee';

ALTER TABLE `student_fees` 	CHANGE `student_id` `security_user_id` INT(11) NOT NULL, 
							CHANGE `institution_fee_id` `institution_site_fee_id` INT(11) NOT NULL;

DROP TABLE IF EXISTS `institution_fee_types`;
ALTER TABLE `z_1414_institution_site_fee_types` RENAME `institution_site_fee_types`;

DROP TABLE IF EXISTS `institution_fees`;
ALTER TABLE `z_1414_institution_site_fees` RENAME `institution_site_fees`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1414';
