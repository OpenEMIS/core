-- PHPOE-1378

UPDATE `security_functions` SET 
`_add` = '_view:financesAdd' 
WHERE `controller` = 'Census' AND `module` = 'Institutions' AND `category` = 'Totals' AND `_view` = 'finances' LIMIT 1;

-- Update version number
UPDATE `config_items` SET `value` = '2.4.6' WHERE `name` = 'db_version';
