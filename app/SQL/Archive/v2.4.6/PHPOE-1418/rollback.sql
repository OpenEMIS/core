-- 
-- PHPOE-1418 rollback.sql
-- 

ALTER TABLE `staff_attachments` DROP `date_on_file`;

UPDATE `navigations` 
SET `action` = 'attachments', `pattern` = 'attachments' 
WHERE `navigations`.`module` = 'Staff' AND `navigations`.`plugin` = 'Staff' AND `navigations`.`controller` = 'Staff' AND `navigations`.`header` = 'General' AND `navigations`.`title` = 'Attachments' ;

UPDATE `security_functions`
SET
	`_view`='attachments|attachmentsDownload|attachmentsView',
	`_edit`='_view:attachmentsEdit',
	`_add`='_view:attachmentsAdd',
	`_delete`='_view:attachmentsDelete'
WHERE `name` = 'Attachments'
AND `controller` = 'Staff'
AND `module` = 'Staff'
AND `category` = 'General';
