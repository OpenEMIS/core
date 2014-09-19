UPDATE `navigations` SET `pattern` = 'index$|systems|levels|cycles|programmes|grades|subjects|certifications|orientations|fields|reorder' WHERE `controller` = 'Education' AND `title` = 'Education Structure';

UPDATE `security_functions` SET
`name` = 'Setup',
`_view` = 'index|systems|levels|cycles|programmes|grades|subjects|certifications|orientations|fields|reorder',
`_edit` = '_view:setupEdit',
`_add` = '_edit:|setupProgrammeAddDialog'
WHERE `controller` = 'Education'
AND `name` = 'Education Systems';

DELETE FROM `security_functions` WHERE `controller` = 'Education' AND `name` IN 
(
	'Education Levels', 'Education Cycles', 'Education Programmes', 'Education Grades', 
	'Education Grade - Subjects', 'Education Subjects', 'Education Certifications',
	'Education Field of Study', 'Education Programme Orientations'
);
