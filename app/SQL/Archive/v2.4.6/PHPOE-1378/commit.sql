UPDATE `security_functions` SET
`_add` = '_view:finances'
WHERE `controller` = 'Census' AND `module` = 'Institutions' AND `category` = 'Totals' AND `_view` = 'finances' LIMIT 1;