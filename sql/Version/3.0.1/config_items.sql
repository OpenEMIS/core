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

