-- remove delete from nav
UPDATE navigations SET pattern = REPLACE(pattern, '|delete', '') WHERE controller = 'FieldOption';

-- reactivate student gender
UPDATE field_options SET visible = 1 WHERE code = 'Gender' AND name = 'Gender' AND parent = 'Student';

ALTER TABLE `institution_site_history` DROP `institution_site_sector_id`;
ALTER TABLE `institution_site_history` DROP `institution_site_provider_id`;
ALTER TABLE `institution_site_history` DROP `institution_site_gender_id`;

ALTER TABLE `field_options` DROP `plugin`;

-- remove country
DELETE FROM field_options WHERE code = 'Country';