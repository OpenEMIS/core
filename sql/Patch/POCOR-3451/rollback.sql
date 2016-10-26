-- Drop tables
DROP TABLE IF EXISTS `institution_visit_requests`;

-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Institution.VisitRequests';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 1047;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3451';
