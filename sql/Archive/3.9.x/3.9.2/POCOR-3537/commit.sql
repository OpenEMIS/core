-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3537', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('ec92914b-e913-11e6-a68b-525400b263eb', 'RubricTemplates', 'name', 'Rubric -> Templates', 'Template', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('f3a106b5-e913-11e6-a68b-525400b263eb', 'RubricSections', 'name', 'Rubric -> Sections', 'Section', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('0823fd83-e914-11e6-a68b-525400b263eb', 'RubricCriterias', 'name', 'Rubric -> Criterias', 'Criteria', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('017c68d8-e914-11e6-a68b-525400b263eb', 'RubricTemplateOptions', 'name', 'Rubric -> Options', 'Option', '1', '1', NOW());
