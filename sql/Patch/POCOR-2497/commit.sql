--
-- POCOR-2497
--

-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2497', NOW());

-- security_functions
UPDATE `security_functions` SET `_view` = 'StaffClasses.index' WHERE `security_functions`.`id` = 7023;

INSERT INTO `security_functions` 
(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) 
VALUES 
('7045', 'Contacts', 'Directories', 'Directory', 'General', '7000', 'Contacts.index|Contacts.view', 'Contacts.edit', 'Contacts.add', 'Contacts.remove', NULL, '7045', '1', '1', NOW()),
('7046', 'Training Needs', 'Directories', 'Directory', 'Staff - Training', '7000', 'TrainingNeeds.index|TrainingNeeds.view', 'TrainingNeeds.edit', 'TrainingNeeds.add', 'TrainingNeeds.remove', NULL, '7046', '1', '1', NOW());
