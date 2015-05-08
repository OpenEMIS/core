-- PHPOE-1378

UPDATE `security_functions` SET 
`_add` = '_view:financesAdd' 
WHERE `controller` = 'Census' AND `module` = 'Institutions' AND `category` = 'Totals' AND `_view` = 'finances' LIMIT 1;

-- 
-- PHPOE-1418 commit.sql
-- 

ALTER TABLE `staff_attachments` ADD `date_on_file` DATE NOT NULL AFTER `file_content`;

UPDATE `navigations` 
SET `action` = 'StaffAttachment', `pattern` = 'StaffAttachment' 
WHERE `navigations`.`module` = 'Staff' AND `navigations`.`plugin` = 'Staff' AND `navigations`.`controller` = 'Staff' AND `navigations`.`header` = 'General' AND `navigations`.`title` = 'Attachments' ;

UPDATE `security_functions`
SET
	`_view`='StaffAttachment|StaffAttachment.index|StaffAttachment.download|StaffAttachment.view',
	`_edit`='_view:StaffAttachment.edit',
	`_add`='_view:StaffAttachment.add',
	`_delete`='_view:StaffAttachment.remove'
WHERE `name` = 'Attachments'
AND `controller` = 'Staff'
AND `module` = 'Staff'
AND `category` = 'General';

-- Update version number
UPDATE `config_items` SET `value` = '2.4.6' WHERE `name` = 'db_version';
