-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3016', NOW());

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
VALUES ('User.Users', 'Identity', 'Number', 14, 4, 'User', 'Identities', 'FieldOption.IdentityTypes');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT NULL, NULL, 'Please Define Default Identity Type', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual 
WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Please Define Default Type!');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT NULL, NULL, 'Staff identity is mandatory', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual 
WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Staff identity is mandatory');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
SELECT NULL, NULL, 'Student identity is mandatory', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual 
WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Student identity is mandatory');