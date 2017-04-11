--
-- PHPOE-2092
--
UPDATE `security_functions` SET `controller`='Education' WHERE `controller`='Educations' AND `module`='Administration' AND `category`='Education';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2092';
