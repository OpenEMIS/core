--
--	commit.sql
--

UPDATE `security_functions`
SET 
	`_view`='attachments|attachmentsDownload|attachmentsView',
	`_edit`='_view:attachmentsEdit',
	`_add`='_view:attachmentsAdd',
	`_delete`='_view:attachmentsDelete'
WHERE
	`name`='Attachments'
AND (
		(`controller`='Staff' AND `module`='Staff' AND `category`='General')
	OR (`controller`='Students' AND `module`='Students' AND `category`='General')
);