UPDATE `navigations` SET `action` = 'behaviourStudentList', `pattern` = 'behaviourStudent' WHERE `controller` = 'InstitutionSites' AND `header` = 'Behaviour' AND `title` = 'Students';
UPDATE `navigations` SET `action` = 'behaviourStaffList', `pattern` = 'behaviourStaff' WHERE `controller` = 'InstitutionSites' AND `header` = 'Behaviour' AND `title` = 'Staff';
UPDATE `navigations` SET `action` = 'behaviour', `pattern` = 'behaviour|^behaviourView$' WHERE `controller` = 'Students' AND `header` = 'Details' AND `title` = 'Behaviour';
UPDATE `navigations` SET `action` = 'behaviour', `pattern` = 'behaviour|^behaviourView$' WHERE `controller` = 'Staff' AND `header` = 'Details' AND `title` = 'Behaviour';

-- Rollback field options
SET @ordering := 0;
SELECT `order` into @ordering FROM `field_options` WHERE `code` = 'StaffBehaviourCategory';
UPDATE `field_options` SET `order` = `order` - 1 WHERE `order` > @ordering;
DELETE FROM `field_options` WHERE `code` = 'StaffBehaviourCategory';

UPDATE `security_functions` SET 
`name` = 'Students - Behaviour', 
`category` = 'Details',
`_view` = 'behaviourStudentList|behaviourStudent|behaviourStudentView', 
`_edit` = '_view:behaviourStudentEdit', 
`_add` = '_view:behaviourStudentAdd', 
`_delete` = '_view:behaviourStudentDelete'
WHERE `controller` = 'InstitutionSites'
AND `module` = 'Institutions'
AND `category` = 'Behaviour'
AND `name` = 'Students';

UPDATE `security_functions` SET 
`_view` = 'behaviour|behaviourView', 
`_edit` = NULL, 
`_add` = NULL, 
`_delete` = NULL
WHERE `name` LIKE 'Behaviour'
AND `controller` LIKE 'Students'
AND `category` LIKE 'Details';

UPDATE `security_functions` SET 
`name` = 'Staff - Behaviour', 
`category` = 'Details',
`_view` = 'behaviourStaffList|behaviourStaff|behaviourStaffView', 
`_edit` = '_view:behaviourStaffEdit', 
`_add` = '_view:behaviourStaffAdd', 
`_delete` = '_view:behaviourStaffDelete'
WHERE `controller` = 'InstitutionSites'
AND `module` = 'Institutions'
AND `category` = 'Behaviour'
AND `name` = 'Staff';

UPDATE `security_functions` SET 
`_view` = 'behaviour|behaviourView', 
`_edit` = NULL, 
`_add` = NULL, 
`_delete` = NULL
WHERE `name` LIKE 'Behaviour'
AND `controller` LIKE 'Staff'
AND `category` LIKE 'Details';

