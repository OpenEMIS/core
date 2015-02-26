--
--	rollback.sql
--

UPDATE `security_functions`
SET 
	`_view`='attachments|attachmentsDownload',
	`_edit`='_view:attachmentsEdit',
	`_add`='_edit:',
	`_delete`='_edit:attachmentsDelete'
WHERE
	`name`='Attachments'
AND (
		(`controller`='Staff' AND `module`='Staff' AND `category`='General')
	OR (`controller`='Students' AND `module`='Students' AND `category`='General')
);