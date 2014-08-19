UPDATE `security_functions` 
SET `_view` = 'classes|classesView|classesStudent|classesStaff|classesSubject',
`_edit` = '_view:classesEdit|classesStudentEdit|classesStaffEdit|classesSubjectEdit'
WHERE `id` = 15
