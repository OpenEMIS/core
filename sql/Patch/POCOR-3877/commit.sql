
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3877', NOW());

ALTER TABLE `security_role_functions`
DROP COLUMN `id`,
DROP PRIMARY KEY,
ADD PRIMARY KEY (`security_role_id`, `security_function_id`);
