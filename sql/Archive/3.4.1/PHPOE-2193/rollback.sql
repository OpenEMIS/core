-- `user_activities`
DROP TABLE `user_activities`;

-- student_activities
ALTER TABLE `z_2193_student_activities` 
RENAME TO  `student_activities` ;

-- staff_activites
ALTER TABLE `z_2193_staff_activities` 
RENAME TO  `staff_activities` ;

-- guardian_activities
ALTER TABLE `z_2193_guardian_activities` 
RENAME TO  `guardian_activities` ;

-- security_functions
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2000;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2001;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2002;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2003;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2004;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2005;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2006;
UPDATE `security_functions` SET `module`='Students', `category`='Academic' WHERE `id`=2007;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2008;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2009;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2010;
UPDATE `security_functions` SET `module`='Students', `category`='Academic' WHERE `id`=2011;
UPDATE `security_functions` SET `module`='Students', `category`='Academic' WHERE `id`=2012;
UPDATE `security_functions` SET `module`='Students', `category`='Academic' WHERE `id`=2013;
UPDATE `security_functions` SET `module`='Students', `category`='Academic' WHERE `id`=2014;
UPDATE `security_functions` SET `module`='Students', `category`='Academic' WHERE `id`=2015;
UPDATE `security_functions` SET `module`='Students', `category`='Academic' WHERE `id`=2016;
UPDATE `security_functions` SET `module`='Students', `category`='Academic' WHERE `id`=2017;
UPDATE `security_functions` SET `module`='Students', `category`='Finance' WHERE `id`=2018;
UPDATE `security_functions` SET `module`='Students', `category`='Finance' WHERE `id`=2019;
UPDATE `security_functions` SET `module`='Students', `category`='General' WHERE `id`=2020;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3000;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3001;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3002;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3003;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3004;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3005;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3006;
UPDATE `security_functions` SET `module`='Staff', `category`='Career' WHERE `id`=3007;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3008;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3009;
UPDATE `security_functions` SET `module`='Staff', `category`='Professional Development' WHERE `id`=3010;
UPDATE `security_functions` SET `module`='Staff', `category`='Professional Development' WHERE `id`=3011;
UPDATE `security_functions` SET `module`='Staff', `category`='Career' WHERE `id`=3012;
UPDATE `security_functions` SET `module`='Staff', `category`='Career' WHERE `id`=3013;
UPDATE `security_functions` SET `module`='Staff', `category`='Career' WHERE `id`=3014;
UPDATE `security_functions` SET `module`='Staff', `category`='Career' WHERE `id`=3015;
UPDATE `security_functions` SET `module`='Staff', `category`='Career' WHERE `id`=3016;
UPDATE `security_functions` SET `module`='Staff', `category`='Career' WHERE `id`=3017;
UPDATE `security_functions` SET `module`='Staff', `category`='Professional Development' WHERE `id`=3018;
UPDATE `security_functions` SET `module`='Staff', `category`='Career' WHERE `id`=3019;
UPDATE `security_functions` SET `module`='Staff', `category`='Finance' WHERE `id`=3020;
UPDATE `security_functions` SET `module`='Staff', `category`='Professional Development' WHERE `id`=3021;
UPDATE `security_functions` SET `module`='Staff', `category`='Professional Development' WHERE `id`=3022;
UPDATE `security_functions` SET `module`='Staff', `category`='Finance' WHERE `id`=3023;
UPDATE `security_functions` SET `module`='Staff', `category`='Training' WHERE `id`=3024;
UPDATE `security_functions` SET `module`='Staff', `category`='Training' WHERE `id`=3025;
UPDATE `security_functions` SET `module`='Staff', `category`='Training' WHERE `id`=3026;
UPDATE `security_functions` SET `module`='Staff', `category`='General' WHERE `id`=3027;

DELETE FROM `security_functions` WHERE `id` >= 7000 AND `id` <= 7035;

-- removal of security_function for guardians module
INSERT INTO `security_functions` SELECT * FROM `z_2193_security_function`;
DROP TABLE `z_2193_security_function`;

-- security_functions (Missing permission for data quality report)
DELETE FROM `security_functions` WHERE `id` = 6007;
UPDATE `security_functions` SET `name`='Audit', `_view`='Audit.index', `_add`='Audit.add', `_execute`='Audit.download' WHERE `id`=6006;
UPDATE `security_functions` SET `name`='InstitutionRubrics' WHERE `id`=6004;

-- removal of security_role_functions
UPDATE `security_role_functions` INNER JOIN `z_2193_security_role_functions` ON `security_role_functions`.`id` = `z_2193_security_role_functions`.`id`
SET `security_role_functions`.`security_function_id` = `z_2193_security_role_functions`.`security_function_id`;
DROP TABLE `z_2193_security_role_functions`;

-- labels
DELETE FROM `labels` WHERE `module` = 'Results' AND `field` = 'assessment_grading_option_id' AND `field_name` = 'Student -> Results';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2193';