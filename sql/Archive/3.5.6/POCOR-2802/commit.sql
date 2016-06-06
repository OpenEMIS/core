-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2802', NOW());


-- code here
UPDATE `security_functions` SET _view = 'StudentFees.index' WHERE id = 2019;