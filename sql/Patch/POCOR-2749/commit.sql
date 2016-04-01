-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2749', NOW());

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Absence - Excused', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Absence - Excused');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Absence - Unexcused', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Absence - Unexcused');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) SELECT NULL, NULL, 'Late', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, now() FROM dual WHERE NOT EXISTS (SELECT * FROM translations WHERE en = 'Late');
