--
-- PHPOE-2092
--
INSERT INTO `db_patches` VALUES ('PHPOE-2092', NOW());

UPDATE `security_functions` SET `controller`='Educations' WHERE `controller`='Education' AND `module`='Administration' AND `category`='Education';