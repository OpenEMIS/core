DELETE FROM config_items where `type`='Nationality' AND `code`='country_id';
DELETE FROM config_items where `type`='Year Book Report';

UPDATE config_items 
SET 
	`name`='Institution Code',
	`code`='institution_code',
	`label`='Institution Code'
WHERE
	`type`='Custom Validation'
AND	`name`='Institution Site Code'
AND	`code`='institution_site_code'
AND	`label`='Institution Site Code';

UPDATE config_items 
SET 
	`name`='Institution Telephone',
	`code`='institution_telephone',
	`label`='Institution Telephone'
WHERE
	`type`='Custom Validation'
AND	`name`='Institution Site Telephone'
AND	`code`='institution_site_telephone'
AND	`label`='Institution Site Telephone';

UPDATE config_items 
SET 
	`name`='Institution Fax',
	`code`='institution_fax',
	`label`='Institution Fax'
WHERE
	`type`='Custom Validation'
AND	`name`='Institution Site Fax'
AND	`code`='institution_site_fax'
AND	`label`='Institution Site Fax';

UPDATE config_items 
SET 
	`name`='Institution Postal Code',
	`code`='institution_postal_code',
	`label`='Institution Postal Code'
WHERE
	`type`='Custom Validation'
AND	`name`='Institution Site Postal Code'
AND	`code`='institution_site_postal_code'
AND	`label`='Institution Site Postal Code';


UPDATE config_items 
SET 
	`type`='Institution',
	`code`='institution_area_level_id'
WHERE
	`type`='Institution Site'
AND	`name`='Display Area Level'
AND	`code`='institution_site_area_level_id'
AND	`label`='Display Area Level';

-- added by jeff
UPDATE `config_item_options` SET `value` = 0 WHERE `option` = 'Sunday';
UPDATE `config_item_options` SET `value` = 1 WHERE `option` = 'Monday';
UPDATE `config_item_options` SET `value` = 2 WHERE `option` = 'Tuesday';
UPDATE `config_item_options` SET `value` = 3 WHERE `option` = 'Wednesday';
UPDATE `config_item_options` SET `value` = 4 WHERE `option` = 'Thursday';
UPDATE `config_item_options` SET `value` = 5 WHERE `option` = 'Friday';
UPDATE `config_item_options` SET `value` = 6 WHERE `option` = 'Saturday';

UPDATE `config_items` SET `value` = 0, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'sunday';
UPDATE `config_items` SET `value` = 1, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'monday';
UPDATE `config_items` SET `value` = 2, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'tuesday';
UPDATE `config_items` SET `value` = 3, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'wednesday';
UPDATE `config_items` SET `value` = 4, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'thursday';
UPDATE `config_items` SET `value` = 5, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'friday';
UPDATE `config_items` SET `value` = 6, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = 'saturday';
UPDATE `config_items` SET `value` = 1, `default_value` = 1 WHERE `name` = 'First Day of Week' AND `value` = '';

