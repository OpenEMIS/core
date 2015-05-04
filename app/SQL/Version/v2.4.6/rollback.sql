-- PHPOE-1378

UPDATE `security_functions` SET
`_add` = '_view:'
WHERE `controller` = 'Census' AND `module` = 'Institutions' AND `category` = 'Totals' AND `_view` = 'finances' LIMIT 1;
