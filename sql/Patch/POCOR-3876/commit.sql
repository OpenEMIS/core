-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3876', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('b7b9aad6-1ff1-11e7-a840-525400b263eb', 'InstitutionClasses', 'multigrade', 'Institutions -> Classes', 'Multi-grade', NULL, NULL, '1', NULL, NULL, '1', '2017-04-13');
