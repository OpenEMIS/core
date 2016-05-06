UPDATE `security_functions` SET
`_view` = 'Sections.index|Sections.view',
`_edit` = 'Sections.edit',
`parent_id` = 1000
WHERE `id` = 1006;

UPDATE `security_functions` SET
`_view` = 'MyClasses.index|MyClasses.view|Sections.index|Sections.view',
`_edit` = 'MyClasses.edit|Sections.edit'
WHERE `id` = 1007;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1821';
