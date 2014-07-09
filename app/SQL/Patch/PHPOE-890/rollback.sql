DELETE FROM navigations
where id = 148;

DELETE FROM navigations
where id = 149;

DROP TABLE IF EXISTS `institution_site_fees`;
DROP TABLE IF EXISTS `institution_site_fee_types`;
DROP TABLE IF EXISTS `institution_site_fee_students`;

DELETE FROM field_option_values where field_option_id=70;