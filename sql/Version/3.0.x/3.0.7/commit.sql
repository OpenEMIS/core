UPDATE `security_functions` SET
`_view` = 'Students.index|Students.view|StudentUser.view',
`_edit` = 'Students.edit|StudentUser.edit',
`_add` = 'Students.add|StudentUser.add'
WHERE `id` = 1012;

UPDATE `security_functions` SET
`_view` = 'Staff.index|Staff.view|StaffUser.view',
`_edit` = 'Staff.edit|StaffUser.edit',
`_add` = 'Staff.add|StaffUser.add'
WHERE `id` = 1016;

UPDATE `config_items` SET `option_type` = 'database:Area.AreaLevels' WHERE `code` = 'institution_area_level_id';

UPDATE `config_items` SET `value` = '3.0.7' WHERE `code` = 'db_version';
