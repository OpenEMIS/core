-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3026', NOW());


-- code here
UPDATE `security_functions` SET _view = 'Assessments.index|Results.index|Assessments.view' WHERE id = 1015;