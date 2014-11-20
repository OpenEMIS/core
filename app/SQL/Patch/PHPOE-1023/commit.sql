UPDATE `navigations` SET `action` = 'StudentBehaviour/show', `pattern` = 'StudentBehaviour' WHERE `controller` = 'InstitutionSites' AND `header` = 'Behaviour' AND `title` = 'Students';
UPDATE `navigations` SET `action` = 'StaffBehaviour/show', `pattern` = 'StaffBehaviour' WHERE `controller` = 'InstitutionSites' AND `header` = 'Behaviour' AND `title` = 'Staff';
UPDATE `navigations` SET `action` = 'StudentBehaviour', `pattern` = 'StudentBehaviour' WHERE `controller` = 'Students' AND `header` = 'Details' AND `title` = 'Behaviour';
UPDATE `navigations` SET `action` = 'StaffBehaviour', `pattern` = 'StaffBehaviour' WHERE `controller` = 'Staff' AND `header` = 'Details' AND `title` = 'Behaviour';

-- Create field options for Staff Behaviour Category
SET @ordering := 0;
SELECT `order` + 1 into @ordering FROM `field_options` WHERE `code` = 'StaffType';
UPDATE `field_options` SET `order` = `order` + 1 WHERE `order` >= @ordering;
INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `created_user_id`, `created`) VALUES
(NULL, 'StaffBehaviourCategory', 'Behaviour Categories', 'Staff', NULL, @ordering, 1, 1, '0000-00-00 00:00:00');

DROP TABLE IF EXISTS `staff_behaviour_categories`;

-- Update security functions for student / staff behaviour
UPDATE `security_functions` SET 
`name` = 'Students', 
`category` = 'Behaviour',
`_view` = 'StudentBehaviour|StudentBehaviour.show|StudentBehaviour.index|StudentBehaviour.view', 
`_edit` = '_view:StudentBehaviour.edit', 
`_add` = '_view:StudentBehaviour.add', 
`_delete` = '_view:StudentBehaviour.remove'
WHERE `controller` = 'InstitutionSites'
AND `module` = 'Institutions'
AND `category` = 'Details'
AND `name` = 'Students - Behaviour';

UPDATE `security_functions` SET 
`_view` = 'StudentBehaviour|StudentBehaviour.index|StudentBehaviour.view', 
`_edit` = NULL, 
`_add` = NULL, 
`_delete` = NULL
WHERE `name` LIKE 'Behaviour'
AND `controller` LIKE 'Students'
AND `category` LIKE 'Details';

UPDATE `security_functions` SET 
`name` = 'Staff', 
`category` = 'Behaviour',
`_view` = 'StaffBehaviour|StaffBehaviour.show|StaffBehaviour.index|StaffBehaviour.view', 
`_edit` = '_view:StaffBehaviour.edit', 
`_add` = '_view:StaffBehaviour.add', 
`_delete` = '_view:StaffBehaviour.remove'
WHERE `controller` = 'InstitutionSites'
AND `module` = 'Institutions'
AND `category` = 'Details'
AND `name` = 'Staff - Behaviour';

UPDATE `security_functions` SET 
`_view` = 'StaffBehaviour|StaffBehaviour.index|StaffBehaviour.view', 
`_edit` = NULL, 
`_add` = NULL, 
`_delete` = NULL
WHERE `name` LIKE 'Behaviour'
AND `controller` LIKE 'Staff'
AND `category` LIKE 'Details';
