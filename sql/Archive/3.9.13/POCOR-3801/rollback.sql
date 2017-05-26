-- security_functions
UPDATE `security_functions` SET `_view` = 'Students.index|Students.view|StudentSurveys.index|StudentSurveys.view' WHERE `id` = 1012;

DELETE FROM `security_functions` WHERE `id` = 2033;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3801';
