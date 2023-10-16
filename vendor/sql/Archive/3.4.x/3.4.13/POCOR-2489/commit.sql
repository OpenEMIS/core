--
-- POCOR-2489
--

-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2489', NOW());

-- security_functions
INSERT INTO `security_functions` 
(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES ('6008', 'Map', 'Map', 'Reports', 'Reports', '-1', 'index', NULL, NULL, NULL, NULL, '6008', '1', '1', NOW());
