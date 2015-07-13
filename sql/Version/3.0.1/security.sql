-- 27th June 2015
UPDATE `security_functions` SET 
	`_edit` = REPLACE(`_edit`, '_view:', ''), 
	`_add` = REPLACE(`_add`, '_view:', ''), 
	`_delete` = REPLACE(`_delete`, '_view:', ''),
	`_execute` = REPLACE(`_execute`, '_view:', '');

UPDATE `security_functions` SET `controller` = 'Institutions' WHERE `controller` = 'InstitutionSites';
UPDATE `security_functions` SET `_view` = 'Attachments.index|Attachments.view', `_add` = 'Attachments.add', `_edit` = 'Attachments.edit', `_delete` = 'Attachments.remove', `_execute` = 'Attachments.download' WHERE `controller` = 'Institutions' AND `name` = 'Attachments';
UPDATE `security_functions` SET `controller` = 'Institutions', `_view` = 'Students.index|Students.view', `_add` = 'Students.add', `_edit` = 'Students.edit', `_delete` = 'Students.remove', `_execute` = 'Students.excel' WHERE `controller` = 'Students' AND `module` = 'Institutions' AND `category` = 'Details' AND `name` = 'Student';
