-- No rollback code needed
DELETE FROM `import_mapping`
WHERE `model` = 'User.Users'
AND `column_name` = 'Identity'
AND `foreign_key` = '4'
AND `lookup_plugin` = 'User'
AND `lookup_model` = 'Identities'
AND `lookup_column` = 'FieldOption.IdentityTypes';

DELETE FROM `translations`
WHERE `en` = 'Please Define Default Identity Type';

DELETE FROM `translations`
WHERE `en` = 'Staff identity is mandatory';

DELETE FROM `translations`
WHERE `en` = 'Student identity is mandatory';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3016';