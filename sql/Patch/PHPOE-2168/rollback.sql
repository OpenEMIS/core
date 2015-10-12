-- restoring data
insert into institution_site_class_students (
	institution_site_class_students.id, 
	institution_site_class_students.status, 
	institution_site_class_students.student_id, 
	institution_site_class_students.institution_site_class_id, 
	institution_site_class_students.institution_site_section_id, 
	institution_site_class_students.modified_user_id, 
	institution_site_class_students.modified, 
	institution_site_class_students.created_user_id, 
	institution_site_class_students.created	
)
(
	select 
	z2168_institution_site_class_students.id, 
	z2168_institution_site_class_students.status, 
	z2168_institution_site_class_students.student_id, 
	z2168_institution_site_class_students.institution_site_class_id, 
	z2168_institution_site_class_students.institution_site_section_id, 
	z2168_institution_site_class_students.modified_user_id, 
	z2168_institution_site_class_students.modified, 
	z2168_institution_site_class_students.created_user_id, 
	z2168_institution_site_class_students.created
	from 
	z2168_institution_site_class_students
);

-- dropping backup table
drop table z2168_institution_site_class_students;