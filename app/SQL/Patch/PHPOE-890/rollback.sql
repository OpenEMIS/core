DELETE FROM navigations
where id between 148 and 150;

DROP TABLE IF EXISTS `institution_site_fees`;
DROP TABLE IF EXISTS `institution_site_fee_types`;
DROP TABLE IF EXISTS `institution_site_student_fees`;
DROP TABLE IF EXISTS `institution_site_student_fee_transactions`;

DELETE FROM field_option_values where field_option_id=70;