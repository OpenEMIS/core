-- code here
DROP TABLE `staff_appraisals`;
DROP TABLE `staff_appraisal_types`;
DROP TABLE `competencies`;
DROP TABLE `competency_sets`;
DROP TABLE `competency_sets_competencies`;
DROP TABLE `staff_appraisals_competencies`;

-- security_function
DELETE FROM `security_functions` WHERE `id` = 3037;
DELETE FROM `security_functions` WHERE `id` = 7049;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `id` BETWEEN 3000 AND 4000 AND `order` >= 3025;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `id` BETWEEN 7000 AND 8000 AND `order` >= 7033;

-- security_role_function
DELETE FROM `security_role_functions` WHERE `security_function_id` = 3037;
DELETE FROM `security_role_functions` WHERE `security_function_id` = 7049;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3450';

