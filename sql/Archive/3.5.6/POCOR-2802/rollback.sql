-- code here
UPDATE `security_functions` SET _view = 'Fees.index' WHERE id = 2019;
	

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2802';