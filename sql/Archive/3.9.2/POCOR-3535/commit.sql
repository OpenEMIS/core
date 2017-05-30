-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3535', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('04865131-e90e-11e6-a68b-525400b263eb', 'SurveyQuestions', 'name', 'Survey -> Questions', 'Question', '1', '1', NOW());
