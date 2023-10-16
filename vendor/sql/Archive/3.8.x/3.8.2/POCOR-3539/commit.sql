-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3539', NOW());

-- translations
INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `editable`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES (NULL, NULL, 'Student has been transferred to', 'وقد تم نقل الطالب ل', '', '', '', '', '1', NULL, NULL, '1', '2016-12-14 00:00:00');

INSERT INTO `translations` (`id`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `editable`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES (NULL, NULL, 'after registration', 'بعد التسجيل', '', '', '', '', '1', NULL, NULL, '1', '2016-12-14 00:00:00');
